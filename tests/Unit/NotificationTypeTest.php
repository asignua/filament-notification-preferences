<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Tests\Unit;

use Asignua\FilamentNotificationPreferences\Support\NotificationType;
use Asignua\FilamentNotificationPreferences\Support\TypeRegistry;
use Asignua\FilamentNotificationPreferences\Tests\TestCase;
use InvalidArgumentException;
use Workbench\App\Notifications\NewComment;
use Workbench\App\Notifications\SecurityAlert;
use Workbench\App\Notifications\UrgentNewComment;
use Workbench\App\Notifications\UrgentSecurityAlert;

class NotificationTypeTest extends TestCase
{
    public function test_the_class_is_the_key_until_a_key_is_given(): void
    {
        $this->assertSame(NewComment::class, NotificationType::make(NewComment::class)->getKey());
        $this->assertSame('comments.new', NotificationType::make(NewComment::class)->key('comments.new')->getKey());
    }

    public function test_defaults_are_mail_and_database_on_and_unlocked(): void
    {
        $type = NotificationType::make(NewComment::class);

        $this->assertSame(['mail', 'database'], $type->getChannels());
        $this->assertTrue($type->getDefault('mail'));
        $this->assertFalse($type->isLocked());
        $this->assertSame('New Comment', $type->getLabel());
        $this->assertSame('General', $type->getGroup());
        $this->assertNull($type->getDescription());
    }

    public function test_channels_accept_names_and_class_to_label_pairs(): void
    {
        $type = NotificationType::make(NewComment::class)->channels(['mail', 'App\\SmsChannel' => 'SMS']);

        $this->assertSame(['mail', 'App\\SmsChannel'], $type->getChannels());
        $this->assertSame('Email', $type->getChannelLabel('mail'));
        $this->assertSame('SMS', $type->getChannelLabel('App\\SmsChannel'));
        $this->assertNull($type->getChannelLabel('database'));
        $this->assertTrue($type->offers('App\\SmsChannel'));
        $this->assertFalse($type->offers('database'));
    }

    public function test_a_custom_channel_without_a_label_gets_a_readable_one(): void
    {
        $this->assertSame('Sms Channel', NotificationType::make(NewComment::class)->channels(['App\\SmsChannel'])->getChannelLabel('App\\SmsChannel'));
    }

    public function test_default_can_be_per_channel_and_a_missing_channel_stays_on(): void
    {
        $type = NotificationType::make(NewComment::class)->default(['mail' => false]);

        $this->assertFalse($type->getDefault('mail'));
        $this->assertTrue($type->getDefault('database'));
    }

    public function test_labels_may_be_closures_resolved_when_asked(): void
    {
        $locale = 'one';
        $type = NotificationType::make(NewComment::class)->label(function () use (&$locale): string {
            return 'Label '.$locale;
        });

        $locale = 'two';

        $this->assertSame('Label two', $type->getLabel());
    }

    public function test_an_empty_or_overlong_key_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NotificationType::make(NewComment::class)->key('');
    }

    public function test_an_overlong_key_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NotificationType::make(NewComment::class)->key(str_repeat('a', 151));
    }

    public function test_a_class_name_that_does_not_fit_the_column_asks_for_a_key(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NotificationType::make('App\\'.str_repeat('Deep\\', 40).'Notification')->getKey();
    }

    public function test_a_type_without_channels_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NotificationType::make(NewComment::class)->channels([]);
    }

    public function test_the_registry_refuses_a_duplicate_key_or_class(): void
    {
        $registry = new TypeRegistry;
        $registry->register(NotificationType::make(NewComment::class)->key('a'));

        try {
            $registry->register(NotificationType::make(UrgentNewComment::class)->key('a'));
            $this->fail('duplicate key accepted');
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(InvalidArgumentException::class);
        $registry->register(NotificationType::make(NewComment::class)->key('b'));
    }

    public function test_the_registry_resolves_by_key_class_and_ancestor(): void
    {
        $registry = new TypeRegistry;
        $type = NotificationType::make(NewComment::class)->key('a');
        $registry->register($type);

        $this->assertSame($type, $registry->find('a'));
        $this->assertSame($type, $registry->find(NewComment::class));
        $this->assertSame($type, $registry->find(UrgentNewComment::class));
        $this->assertNull($registry->find('unknown'));
        $this->assertNull($registry->find(\stdClass::class));
    }

    public function test_registering_again_after_a_lookup_does_not_serve_a_stale_miss(): void
    {
        $registry = new TypeRegistry;
        $this->assertNull($registry->find(UrgentNewComment::class));

        $registry->register(NotificationType::make(NewComment::class));

        $this->assertNotNull($registry->find(UrgentNewComment::class));
    }

    public function test_a_key_equal_to_the_class_of_another_type_is_refused(): void
    {
        $registry = new TypeRegistry;
        $registry->register(NotificationType::make(SecurityAlert::class)->key('security.alert')->locked());

        $this->expectException(InvalidArgumentException::class);

        $registry->register(NotificationType::make(NewComment::class)->key(SecurityAlert::class));
    }

    public function test_a_class_equal_to_the_key_of_another_type_is_refused(): void
    {
        $registry = new TypeRegistry;
        $registry->register(NotificationType::make(NewComment::class)->key(SecurityAlert::class));

        $this->expectException(InvalidArgumentException::class);

        $registry->register(NotificationType::make(SecurityAlert::class)->key('security.alert')->locked());
    }

    public function test_an_unlocked_type_for_a_subclass_of_a_locked_one_is_refused_in_either_order(): void
    {
        $registry = new TypeRegistry;
        $registry->register(NotificationType::make(SecurityAlert::class)->key('security.alert')->locked());

        try {
            $registry->register(NotificationType::make(UrgentSecurityAlert::class)->key('security.urgent'));
            $this->fail('A subclass of a locked notification was registered unlocked.');
        } catch (InvalidArgumentException) {
            $this->assertNull($registry->find('security.urgent'));
        }

        $other = new TypeRegistry;
        $other->register(NotificationType::make(UrgentSecurityAlert::class)->key('security.urgent'));

        $this->expectException(InvalidArgumentException::class);

        $other->register(NotificationType::make(SecurityAlert::class)->key('security.alert')->locked());
    }

    public function test_a_locked_subclass_of_a_locked_type_is_fine(): void
    {
        $registry = new TypeRegistry;
        $registry->register(NotificationType::make(SecurityAlert::class)->key('security.alert')->locked());
        $registry->register(NotificationType::make(UrgentSecurityAlert::class)->key('security.urgent')->locked());

        $this->assertCount(2, $registry->all());
    }
}
