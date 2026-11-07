<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Support;

use Asignua\FilamentNotificationPreferences\Models\NotificationPreference;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Reads and writes the stored deviations of one recipient.
 *
 * Registered as a SCOPED binding: the in-memory memo lives for one request or one queue job and is thrown away
 * by Octane and by the queue worker between them, so an answer never outlives the unit of work that read it.
 * The memo is what makes a mass send cost one query per recipient (not per notification and channel).
 */
final class PreferenceStore
{
    /** @var array<string, array<string, array<string, bool>>> recipient => type key => channel => enabled */
    private array $memo = [];

    /**
     * @return array<string, array<string, bool>> type key => channel => enabled, only what the recipient changed
     */
    public function stored(Model $notifiable): array
    {
        $identity = $this->identity($notifiable);

        return $this->memo[$identity] ??= $this->load($notifiable, $identity);
    }

    /**
     * Writes the choices of the recipient: `$set` are deviations to store, `$clear` are pairs that went back to the
     * default (their row is removed, which is what lets a later change of the default reach this user).
     *
     * @param list<array{0: string, 1: string, 2: bool}> $set   [type key, channel, enabled]
     * @param list<array{0: string, 1: string}>          $clear [type key, channel]
     */
    public function write(Model $notifiable, array $set, array $clear): void
    {
        $model = self::model();
        $type = $notifiable->getMorphClass();
        $id = (string) $notifiable->getKey();

        $model->getConnection()->transaction(function () use ($model, $type, $id, $set, $clear): void {
            if ($clear !== []) {
                $model->newQuery()
                    ->where('notifiable_type', $type)
                    ->where('notifiable_id', $id)
                    ->where(function ($query) use ($clear): void {
                        foreach ($clear as [$key, $channel]) {
                            $query->orWhere(fn ($pair) => $pair->where('type_key', $key)->where('channel', $channel));
                        }
                    })
                    ->delete();
            }

            if ($set !== []) {
                $now = now();

                $model->newQuery()->upsert(
                    array_map(fn (array $row): array => [
                        'notifiable_type' => $type,
                        'notifiable_id' => $id,
                        'type_key' => $row[0],
                        'channel' => $row[1],
                        'enabled' => $row[2],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], $set),
                    ['notifiable_type', 'notifiable_id', 'type_key', 'channel'],
                    ['enabled', 'updated_at'],
                );
            }
        });

        $this->forget($notifiable);
    }

    public function forget(Model $notifiable): void
    {
        $identity = $this->identity($notifiable);

        unset($this->memo[$identity]);

        $store = self::cacheStore();

        if ($store !== null) {
            try {
                $store->forget(self::cacheKey($identity));
            } catch (Throwable $e) {
                // A cache that cannot be cleared must not fail the save; the entry expires with its TTL.
                report($e);
            }
        }
    }

    /**
     * @return array<string, array<string, bool>>
     */
    private function load(Model $notifiable, string $identity): array
    {
        $store = self::cacheStore();

        if ($store === null) {
            return $this->query($notifiable);
        }

        try {
            $cached = $store->get(self::cacheKey($identity));
        } catch (Throwable $e) {
            report($e);
            $cached = null;
        }

        if (is_array($cached)) {
            /** @var array<string, array<string, bool>> $cached */
            return $cached;
        }

        $stored = $this->query($notifiable);

        try {
            $store->put(self::cacheKey($identity), $stored, max(1, (int) config('filament-notification-preferences.cache.ttl', 3600)));
        } catch (Throwable $e) {
            report($e);
        }

        return $stored;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    private function query(Model $notifiable): array
    {
        $stored = [];

        $rows = self::model()->newQuery()
            ->where('notifiable_type', $notifiable->getMorphClass())
            ->where('notifiable_id', (string) $notifiable->getKey())
            ->get(['type_key', 'channel', 'enabled']);

        foreach ($rows as $row) {
            $stored[(string) $row->getAttribute('type_key')][(string) $row->getAttribute('channel')] = (bool) $row->getAttribute('enabled');
        }

        return $stored;
    }

    private function identity(Model $notifiable): string
    {
        return $notifiable->getMorphClass().'|'.$notifiable->getKey();
    }

    private static function cacheKey(string $identity): string
    {
        return 'filament-notification-preferences:'.sha1($identity);
    }

    /**
     * The store to use, or null when caching is off or the store is per-process (see the config).
     */
    private static function cacheStore(): ?Repository
    {
        if (!(bool) config('filament-notification-preferences.cache.enabled', true)) {
            return null;
        }

        $name = config('filament-notification-preferences.cache.store');
        $name = is_string($name) && $name !== '' ? $name : (string) config('cache.default');
        $driver = config('cache.stores.'.$name.'.driver');

        if (!is_string($driver) || in_array($driver, ['array', 'null'], true)) {
            return null;
        }

        return Cache::store($name);
    }

    private static function model(): NotificationPreference
    {
        $class = config('filament-notification-preferences.model', NotificationPreference::class);

        $model = is_string($class) && is_a($class, NotificationPreference::class, true) ? new $class : new NotificationPreference;

        return $model;
    }
}
