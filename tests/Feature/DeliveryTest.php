<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Tests\Feature;

use Asignua\FilamentNotificationPreferences\Models\NotificationPreference;
use Asignua\FilamentNotificationPreferences\NotificationPreferences;
use Asignua\FilamentNotificationPreferences\Tests\Support\Sandbox;
use Asignua\FilamentNotificationPreferences\Tests\Support\ThrowingPreference;
use Asignua\FilamentNotificationPreferences\Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use Workbench\App\Notifications\InvoicePaid;
use Workbench\App\Notifications\MentionedYou;
use Workbench\App\Notifications\NewComment;
use Workbench\App\Notifications\SecurityAlert;
use Workbench\App\Notifications\UrgentNewComment;
use Workbench\App\Notifications\WeeklyDigest;

class DeliveryTest extends TestCase
{
    use Sandbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useArrayMailer();
    }

    public function test_a_declared_type_is_delivered_by_default(): void
    {
        $user = $this->makeUser();

        $user->notify(new NewComment);

        $this->assertSame(1, $this->inAppCount($user));
        $this->assertSame(1, $this->sentMailCount());
    }

    public function test_a_disabled_pair_is_not_delivered_but_the_other_channel_still_is(): void
    {
        $user = $this->makeUser();
        NotificationPreferences::for($user)->save(['comments.new' => ['mail' => false]]);

        $user->notify(new NewComment);

        $this->assertSame(1, $this->inAppCount($user));
        $this->assertSame(0, $this->sentMailCount());
    }

    public function test_a_type_that_is_off_by_default_is_not_delivered_until_the_user_turns_it_on(): void
    {
        $user = $this->makeUser();

        $user->notify(new WeeklyDigest);
        $this->assertSame(0, $this->sentMailCount());

        NotificationPreferences::for($user)->save(['digests.weekly' => ['mail' => true]]);
        $user->notify(new WeeklyDigest);

        $this->assertSame(1, $this->sentMailCount());
    }

    public function test_one_user_turning_a_type_off_does_not_affect_another(): void
    {
        $quiet = $this->makeUser('Quiet');
        $loud = $this->makeUser('Loud');
        NotificationPreferences::for($quiet)->save(['comments.new' => ['mail' => false, 'database' => false]]);

        Notification::send([$quiet, $loud], new NewComment);

        $this->assertSame(0, $this->inAppCount($quiet));
        $this->assertSame(1, $this->inAppCount($loud));
    }

    public function test_a_custom_channel_is_controlled_by_its_class_name(): void
    {
        $sink = tempnam(sys_get_temp_dir(), 'npref');
        putenv('NPREF_SINK='.$sink);

        try {
            $user = $this->makeUser();
            $user->notify(new InvoicePaid);
            $this->assertSame('', (string) file_get_contents($sink), 'SMS is off by default.');

            NotificationPreferences::for($user)->save(['billing.invoice-paid' => [\Workbench\App\Channels\SinkChannel::class => true]]);
            $user->notify(new InvoicePaid);
            $this->assertStringContainsString(InvoicePaid::class, (string) file_get_contents($sink));
        } finally {
            putenv('NPREF_SINK');
            @unlink($sink);
        }
    }

    public function test_a_locked_type_is_always_delivered_even_when_a_row_says_otherwise(): void
    {
        $user = $this->makeUser();

        // What a forged request or a hand edit of the table could leave behind: the listener must not trust it.
        foreach (['mail', 'database'] as $channel) {
            NotificationPreference::query()->create([
                'notifiable_type' => $user->getMorphClass(),
                'notifiable_id' => (string) $user->getKey(),
                'type_key' => 'security.alert',
                'channel' => $channel,
                'enabled' => false,
            ]);
        }

        $user->notify(new SecurityAlert);

        $this->assertSame(1, $this->inAppCount($user));
        $this->assertSame(1, $this->sentMailCount());
        $this->assertTrue(NotificationPreferences::for($user)->allows(SecurityAlert::class, 'mail'));
    }

    public function test_a_notification_nobody_declared_is_always_sent(): void
    {
        NotificationPreferences::types()->flush();
        $user = $this->makeUser();

        $user->notify(new NewComment);

        $this->assertSame(1, $this->inAppCount($user));
    }

    public function test_an_anonymous_recipient_always_gets_the_notification(): void
    {
        NotificationPreferences::types()->flush();
        NotificationPreferences::register(\Asignua\FilamentNotificationPreferences\Support\NotificationType::make(NewComment::class)->default(false));

        Notification::route('mail', 'guest@example.com')->notify(new NewComment);

        $this->assertSame(1, $this->sentMailCount());
    }

    public function test_a_subclass_follows_the_choices_for_its_declared_parent(): void
    {
        $user = $this->makeUser();
        NotificationPreferences::for($user)->save(['comments.new' => ['mail' => false]]);

        $user->notify(new UrgentNewComment);

        $this->assertSame(1, $this->inAppCount($user));
        $this->assertSame(0, $this->sentMailCount());
    }

    public function test_the_nearest_declared_ancestor_wins(): void
    {
        NotificationPreferences::register(
            \Asignua\FilamentNotificationPreferences\Support\NotificationType::make(UrgentNewComment::class)->key('comments.urgent')->channels(['mail']),
        );
        $user = $this->makeUser();
        NotificationPreferences::for($user)->save(['comments.new' => ['mail' => false], 'comments.urgent' => ['mail' => true]]);

        $this->assertTrue(NotificationPreferences::for($user)->allows(UrgentNewComment::class, 'mail'));
        $this->assertFalse(NotificationPreferences::for($user)->allows(NewComment::class, 'mail'));
    }

    public function test_a_channel_the_type_does_not_offer_is_not_governed(): void
    {
        $user = $this->makeUser();

        $this->assertTrue(NotificationPreferences::for($user)->allows(WeeklyDigest::class, 'database'));
        $this->assertTrue(NotificationPreferences::for($user)->allows(WeeklyDigest::class, 'whatever'));
    }

    public function test_a_failure_while_reading_the_choices_lets_the_notification_through(): void
    {
        $user = $this->makeUser();
        NotificationPreferences::for($user)->save(['comments.new' => ['mail' => false, 'database' => false]]);

        // No DDL here: it would commit the test transaction on MariaDB and abort it on Postgres. A model whose query
        // blows up is the same failure from the package's point of view.
        config(['filament-notification-preferences.model' => ThrowingPreference::class]);
        app()->forgetScopedInstances();

        $user->notify(new NewComment);

        $this->assertSame(1, $this->inAppCount($user), 'the in-app notification was delivered');
        $this->assertSame(1, $this->sentMailCount(), 'the mail was delivered');
    }

    public function test_a_default_changed_by_the_developer_reaches_users_who_never_touched_the_page(): void
    {
        $untouched = $this->makeUser('Untouched');
        $chose = $this->makeUser('Chose');
        NotificationPreferences::for($chose)->save(['digests.weekly' => ['mail' => true]]);

        $this->assertFalse(NotificationPreferences::for($untouched)->allows(WeeklyDigest::class, 'mail'));

        NotificationPreferences::types()->flush();
        NotificationPreferences::register(
            \Asignua\FilamentNotificationPreferences\Support\NotificationType::make(WeeklyDigest::class)->key('digests.weekly')->channels(['mail'])->default(true),
        );
        app()->forgetScopedInstances();

        $this->assertTrue(NotificationPreferences::for($untouched)->allows(WeeklyDigest::class, 'mail'));
        $this->assertTrue(NotificationPreferences::for($chose)->allows(WeeklyDigest::class, 'mail'));
    }

    public function test_only_deviations_are_stored(): void
    {
        $user = $this->makeUser();
        $preferences = NotificationPreferences::for($user);

        // Everything at its default: nothing to store.
        $preferences->save(['comments.new' => ['mail' => true, 'database' => true], 'comments.mention' => ['mail' => false, 'database' => true]]);
        $this->assertSame(0, NotificationPreference::query()->count());

        $preferences->save(['comments.new' => ['mail' => false]]);
        $this->assertSame(1, NotificationPreference::query()->count());

        // Going back to the default removes the row again.
        $preferences->save(['comments.new' => ['mail' => true]]);
        $this->assertSame(0, NotificationPreference::query()->count());
    }

    public function test_mentions_default_per_channel(): void
    {
        $user = $this->makeUser();

        $user->notify(new MentionedYou);

        $this->assertSame(1, $this->inAppCount($user));
        $this->assertSame(0, $this->sentMailCount());
    }

    public function test_filaments_send_to_database_is_all_or_nothing_through_its_one_class(): void
    {
        // sendToDatabase() wraps every message in the same class, so the only handle is that class: one switch for all of them.
        NotificationPreferences::register(
            \Asignua\FilamentNotificationPreferences\Support\NotificationType::make(\Filament\Notifications\DatabaseNotification::class)
                ->key('panel.messages')->label('Panel messages')->channels(['database']),
        );
        $user = $this->makeUser();

        \Filament\Notifications\Notification::make()->title('Hello')->sendToDatabase($user);
        $this->assertSame(1, $this->inAppCount($user));

        NotificationPreferences::for($user)->save(['panel.messages' => ['database' => false]]);
        \Filament\Notifications\Notification::make()->title('Hello again')->sendToDatabase($user);

        $this->assertSame(1, $this->inAppCount($user));
    }
}
