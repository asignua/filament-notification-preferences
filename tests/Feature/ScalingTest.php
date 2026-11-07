<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Tests\Feature;

use Asignua\FilamentNotificationPreferences\NotificationPreferences;
use Asignua\FilamentNotificationPreferences\Support\PreferenceStore;
use Asignua\FilamentNotificationPreferences\Tests\Support\Sandbox;
use Asignua\FilamentNotificationPreferences\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Workbench\App\Notifications\InvoicePaid;
use Workbench\App\Notifications\NewComment;

class ScalingTest extends TestCase
{
    use Sandbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useArrayMailer();
    }

    public function test_a_mass_send_reads_the_choices_once_per_recipient(): void
    {
        $users = collect(range(1, 25))->map(fn (int $i) => $this->makeUser("User {$i}"));
        $users->each(fn ($user) => NotificationPreferences::for($user)->save(['comments.new' => ['mail' => false]]));
        app()->forgetScopedInstances();

        // Three channels and a handful of declared types per recipient, still one SELECT each.
        $queries = $this->preferenceQueries(fn () => Notification::send($users, new InvoicePaid));

        $this->assertCount(25, $queries);
    }

    public function test_the_shared_cache_makes_a_second_send_free_and_a_save_invalidates_it(): void
    {
        config(['cache.stores.shared' => ['driver' => 'file', 'path' => storage_path('framework/cache/npref-'.uniqid())]]);
        config(['filament-notification-preferences.cache.store' => 'shared']);

        $users = collect(range(1, 5))->map(fn (int $i) => $this->makeUser("User {$i}"));

        $first = $this->preferenceQueries(fn () => Notification::send($users, new NewComment));
        $this->assertCount(5, $first);

        app()->forgetScopedInstances();
        $second = $this->preferenceQueries(fn () => Notification::send($users, new NewComment));
        $this->assertCount(0, $second, 'served from the cache');

        // The user saves: the cached answer must not outlive it, also for a process that holds an old memo.
        NotificationPreferences::for($users[0])->save(['comments.new' => ['mail' => false]]);
        app()->forgetScopedInstances();

        $this->assertFalse(NotificationPreferences::for($users[0])->allows(NewComment::class, 'mail'));
        $this->assertTrue(NotificationPreferences::for($users[1])->allows(NewComment::class, 'mail'));
    }

    public function test_a_reader_that_loaded_before_a_save_cannot_put_the_old_answer_back_after_it(): void
    {
        config(['cache.stores.shared' => ['driver' => 'file', 'path' => storage_path('framework/cache/npref-'.uniqid())]]);
        config(['filament-notification-preferences.cache.store' => 'shared']);

        $user = $this->makeUser();
        $writing = false;
        $table = (string) config('filament-notification-preferences.table');

        // The reader has just read the OLD rows; before it caches them, another process saves and invalidates.
        DB::listen(function ($query) use (&$writing, $user, $table): void {
            if (!$writing && str_starts_with(ltrim($query->sql), 'select') && str_contains($query->sql, $table)) {
                $writing = true;
                NotificationPreferences::for($user)->save(['comments.new' => ['mail' => false]]);
            }
        });

        $reader = new PreferenceStore;
        $this->assertSame([], $reader->stored($user));

        $this->assertFalse((new PreferenceStore)->stored($user) === [], 'the old answer was cached after the save');
        $this->assertFalse(NotificationPreferences::for($user)->allows('comments.new', 'mail'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function perProcessDrivers(): array
    {
        return ['apc' => ['apc'], 'octane' => ['octane'], 'session' => ['session'], 'array' => ['array']];
    }

    #[DataProvider('perProcessDrivers')]
    public function test_a_store_that_is_not_shared_between_processes_is_never_used(string $driver): void
    {
        config(['cache.stores.local' => ['driver' => $driver], 'filament-notification-preferences.cache.store' => 'local']);

        $user = $this->makeUser();

        $first = $this->preferenceQueries(fn () => NotificationPreferences::for($user)->allows(NewComment::class, 'mail'));
        app()->forgetScopedInstances();
        $second = $this->preferenceQueries(fn () => NotificationPreferences::for($user)->allows(NewComment::class, 'mail'));

        $this->assertCount(1, $first);
        $this->assertCount(1, $second, 'read from the database again, not from a per-process store');
    }

    public function test_a_per_process_cache_store_is_never_used(): void
    {
        // The default test store is `array`: a worker would keep a stale answer forever, so no cache is written.
        $user = $this->makeUser();

        NotificationPreferences::for($user)->allows(NewComment::class, 'mail');

        $this->assertSame('array', config('cache.stores.'.config('cache.default').'.driver'));
        $this->assertSame([], array_filter(
            array_keys((fn () => $this->storage)->call(Cache::store('array')->getStore())),
            fn ($key) => str_starts_with((string) $key, 'filament-notification-preferences'),
        ));
    }

    public function test_the_memo_lives_in_a_scoped_binding_that_octane_and_the_worker_reset(): void
    {
        $first = app(PreferenceStore::class);

        $this->assertSame($first, app(PreferenceStore::class));

        app()->forgetScopedInstances();

        $this->assertNotSame($first, app(PreferenceStore::class));
    }

    public function test_a_choice_changed_elsewhere_is_seen_after_the_scope_is_reset(): void
    {
        $user = $this->makeUser();
        $this->assertTrue(NotificationPreferences::for($user)->allows(NewComment::class, 'mail'));

        // Another process (the web request) stores the choice behind this scope's back.
        \Asignua\FilamentNotificationPreferences\Models\NotificationPreference::query()->create([
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => (string) $user->getKey(),
            'type_key' => 'comments.new',
            'channel' => 'mail',
            'enabled' => false,
        ]);

        $this->assertTrue(NotificationPreferences::for($user)->allows(NewComment::class, 'mail'), 'inside one scope the memo answers');

        app()->forgetScopedInstances();

        $this->assertFalse(NotificationPreferences::for($user)->allows(NewComment::class, 'mail'));
    }
}
