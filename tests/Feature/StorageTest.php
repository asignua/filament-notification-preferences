<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Tests\Feature;

use Asignua\FilamentNotificationPreferences\Models\NotificationPreference;
use Asignua\FilamentNotificationPreferences\NotificationPreferences;
use Asignua\FilamentNotificationPreferences\Support\NotificationType;
use Asignua\FilamentNotificationPreferences\Tests\Support\Sandbox;
use Asignua\FilamentNotificationPreferences\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Workbench\App\Notifications\NewComment;

class StorageTest extends TestCase
{
    use Sandbox;

    /**
     * @return array<string, mixed>
     */
    private function row(string $type = 'comments.new', string $channel = 'mail', string $id = '7'): array
    {
        return ['notifiable_type' => 'user', 'notifiable_id' => $id, 'type_key' => $type, 'channel' => $channel, 'enabled' => false];
    }

    public function test_a_second_answer_for_the_same_recipient_type_and_channel_is_refused_by_the_database(): void
    {
        NotificationPreference::query()->create($this->row());

        $this->expectException(QueryException::class);

        NotificationPreference::query()->create($this->row());
    }

    public function test_saving_twice_updates_the_row_instead_of_adding_one(): void
    {
        $user = $this->makeUser();
        $preferences = NotificationPreferences::for($user);

        $preferences->save(['comments.new' => ['mail' => false]]);
        $preferences->save(['comments.new' => ['mail' => false]]);

        $this->assertSame(1, NotificationPreference::query()->count());
        $this->assertFalse($preferences->allows('comments.new', 'mail'));
    }

    public function test_a_flip_of_an_existing_deviation_rewrites_it(): void
    {
        $user = $this->makeUser();
        $preferences = NotificationPreferences::for($user);

        // digests.weekly is off by default: on is the deviation; changing it needs "on" to stay a deviation of a different shape.
        NotificationPreferences::types()->flush();
        NotificationPreferences::register(NotificationType::make(NewComment::class)->key('c')->channels(['mail'])->default(false));

        $preferences->save(['c' => ['mail' => true]]);
        $this->assertTrue($preferences->allows('c', 'mail'));

        NotificationPreference::query()->update(['enabled' => false]);
        app()->forgetScopedInstances();

        $this->assertFalse(NotificationPreferences::for($user)->allows('c', 'mail'));
    }

    public function test_uuid_and_ulid_recipients_are_stored_and_found(): void
    {
        foreach ([(string) Str::uuid(), (string) Str::ulid()] as $key) {
            $recipient = new class extends Model
            {
                public $incrementing = false;

                protected $keyType = 'string';

                protected $guarded = [];
            };
            $recipient->setAttribute('id', $key);

            NotificationPreferences::for($recipient)->save(['comments.new' => ['mail' => false]]);
            app()->forgetScopedInstances();

            $this->assertFalse(NotificationPreferences::for($recipient)->allows('comments.new', 'mail'), $key);
            $this->assertTrue(NotificationPreferences::for($recipient)->allows('comments.new', 'database'), $key);
        }
    }

    public function test_the_unique_index_fits_the_innodb_limit_in_utf8mb4_bytes(): void
    {
        // SQLite does not care about index length, MariaDB does: read the lengths from the migration itself.
        $stub = (string) file_get_contents(__DIR__.'/../../database/migrations/create_notification_preferences_table.php.stub');

        $bytes = 0;

        foreach (['notifiable_type', 'notifiable_id', 'type_key', 'channel'] as $name) {
            $length = preg_match("/string\\('{$name}'(?:, (\\d+))?\\)/", $stub, $match) === 1 ? (int) ($match[1] ?? 255) : 0;
            $this->assertGreaterThan(0, $length, $name);
            $bytes += $length * 4;
        }

        $this->assertLessThan(3072, $bytes);
    }

    public function test_the_table_and_connection_are_configurable(): void
    {
        config(['filament-notification-preferences.table' => 'custom_prefs']);

        $this->assertSame('custom_prefs', (new NotificationPreference)->getTable());
    }

    public function test_the_full_length_key_and_a_long_class_channel_round_trip(): void
    {
        $key = str_repeat('k', 150);
        $channel = 'App\\Notifications\\Channels\\'.str_repeat('X', 100);

        NotificationPreference::query()->create($this->row($key, $channel));

        $found = NotificationPreference::query()->where('type_key', $key)->where('channel', $channel)->first();

        $this->assertNotNull($found);
        $this->assertFalse($found->enabled);
    }
}
