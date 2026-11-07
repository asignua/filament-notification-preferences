<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Tests\Feature;

use Asignua\FilamentNotificationPreferences\NotificationPreferencesPlugin;
use Asignua\FilamentNotificationPreferences\Pages\NotificationPreferencesPage;
use Asignua\FilamentNotificationPreferences\Tests\TestCase;
use Filament\Facades\Filament;

class SmokeTest extends TestCase
{
    public function test_the_panel_boots(): void
    {
        $this->assertSame('admin', Filament::getCurrentPanel()?->getId());
    }

    public function test_the_plugin_is_registered_on_the_panel(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertTrue($panel->hasPlugin(NotificationPreferencesPlugin::ID));
        $this->assertInstanceOf(NotificationPreferencesPlugin::class, $panel->getPlugin(NotificationPreferencesPlugin::ID));
    }

    public function test_the_translations_are_loaded(): void
    {
        $this->assertSame('Notification preferences', __('filament-notification-preferences::filament-notification-preferences.page.title'));
    }

    public function test_the_page_is_in_the_navigation_group_the_plugin_names(): void
    {
        $this->assertSame('Settings', NotificationPreferencesPage::getNavigationGroup());
        $this->assertSame('Notification preferences', NotificationPreferencesPage::getNavigationLabel());
        $this->assertTrue(NotificationPreferencesPage::shouldRegisterNavigation());
    }
}
