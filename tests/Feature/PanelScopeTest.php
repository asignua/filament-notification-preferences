<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Tests\Feature;

use Asignua\FilamentNotificationPreferences\NotificationPreferencesPlugin;
use Asignua\FilamentNotificationPreferences\Pages\NotificationPreferencesPage;
use Asignua\FilamentNotificationPreferences\Tests\Support\Sandbox;
use Asignua\FilamentNotificationPreferences\Tests\TestCase;
use Filament\Facades\Filament;
use Filament\Panel;
use Workbench\App\Models\User;

class PanelScopeTest extends TestCase
{
    use Sandbox;

    public function test_the_slug_is_read_from_the_panel_that_is_asked_not_from_the_current_one(): void
    {
        $other = Panel::make()->id('other')->path('other')->plugin(NotificationPreferencesPlugin::make()->slug('my-prefs'));

        $this->assertSame('notification-preferences', Filament::getCurrentPanel()?->getPlugin(NotificationPreferencesPlugin::ID)->getSlug());
        $this->assertSame('my-prefs', NotificationPreferencesPage::getSlug($other));
        $this->assertSame('notification-preferences', NotificationPreferencesPage::getSlug(Filament::getPanel('admin')));
    }

    public function test_the_user_menu_link_is_hidden_on_a_tenant_panel_without_a_tenant(): void
    {
        $panel = Panel::make()->id('tenanted')->path('tenanted')->tenant(User::class)
            ->plugin(NotificationPreferencesPlugin::make()->userMenu());

        Filament::setCurrentPanel($panel);
        Filament::setTenant(null, true);

        $this->assertArrayNotHasKey('notification-preferences', $panel->getUserMenuItems());
    }

    public function test_the_user_menu_link_shows_on_a_panel_without_tenancy(): void
    {
        $this->assertArrayHasKey('notification-preferences', Filament::getPanel('admin')->getUserMenuItems());
    }
}
