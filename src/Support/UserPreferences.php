<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Support;

use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * The notification choices of one recipient: `NotificationPreferences::for($user)`.
 */
final class UserPreferences
{
    public function __construct(
        private readonly Model $notifiable,
        private readonly TypeRegistry $types,
        private readonly PreferenceStore $store,
    ) {}

    /**
     * May this recipient get this type on this channel?
     *
     * Fails OPEN: whatever is not declared, whatever is locked, a channel the type does not offer and an error
     * while reading the choices all answer true. A preference page must never be the reason a mail is lost.
     *
     * @param object|string $type a type key, a notification class or a notification instance
     */
    public function allows(string|object $type, string $channel): bool
    {
        try {
            // An object is a notification: only its class (or the nearest declared parent) can name its type, never a key.
            $declared = is_object($type) ? $this->types->resolveClass($type::class) : $this->types->find($type);

            if ($declared === null || $declared->isLocked() || !$declared->offers($channel)) {
                return true;
            }

            return $this->store->stored($this->notifiable)[$declared->getKey()][$channel] ?? $declared->getDefault($channel);
        } catch (Throwable $e) {
            report($e);

            return true;
        }
    }

    /**
     * What the recipient gets now, for every declared type and every channel the type offers (a locked type is
     * on). Unlike allows() this throws when the choices cannot be read: the page must not show a guess.
     *
     * @return array<string, array<string, bool>> type key => channel => on
     */
    public function matrix(): array
    {
        $stored = $this->store->stored($this->notifiable);
        $matrix = [];

        foreach ($this->types->all() as $key => $type) {
            foreach ($type->getChannels() as $channel) {
                $matrix[$key][$channel] = $type->isLocked() || ($stored[$key][$channel] ?? $type->getDefault($channel));
            }
        }

        return $matrix;
    }

    /**
     * Stores the wanted state. Only declared, unlocked types and the channels they offer are looked at; anything
     * else in `$wanted` (a forged key, a locked type, a channel the type lacks, a non-boolean) is ignored.
     * A value equal to the default removes the row instead of storing it, so the default keeps governing.
     *
     * @param array<array-key, mixed> $wanted type key => channel => bool
     */
    public function save(array $wanted): void
    {
        $set = [];
        $clear = [];

        foreach ($this->types->all() as $key => $type) {
            $row = $wanted[$key] ?? null;

            if ($type->isLocked() || !is_array($row)) {
                continue;
            }

            foreach ($type->getChannels() as $channel) {
                if (!array_key_exists($channel, $row) || !is_bool($row[$channel])) {
                    continue;
                }

                if ($row[$channel] === $type->getDefault($channel)) {
                    $clear[] = [$key, $channel];
                } else {
                    $set[] = [$key, $channel, $row[$channel]];
                }
            }
        }

        $this->store->write($this->notifiable, $set, $clear);
    }

    /**
     * Drops the remembered choices (after the table was edited behind the package's back).
     */
    public function forget(): void
    {
        $this->store->forget($this->notifiable);
    }
}
