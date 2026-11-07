<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Tests\Feature;

use Asignua\FilamentNotificationPreferences\NotificationPreferencesPlugin;
use Asignua\FilamentNotificationPreferences\Pages\NotificationPreferencesPage;
use Asignua\FilamentNotificationPreferences\Tests\Support\Sandbox;
use Asignua\FilamentNotificationPreferences\Tests\TestCase;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Panel;
use ReflectionProperty;
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

        $this->assertFalse($this->menuItem($panel)->isVisible());
    }

    public function test_the_user_menu_link_shows_on_a_panel_without_tenancy(): void
    {
        $this->assertTrue($this->menuItem(Filament::getPanel('admin'))->isVisible());
    }

    private function menuItem(Panel $panel): Action
    {
        // Read the declared item itself: getUserMenuItems() would also build the logout URL, whose route a bare test panel lacks.
        $groups = (new ReflectionProperty($panel, 'userMenuItemGroups'))->getValue($panel);

        return collect($groups)->collapse()->get('notification-preferences');
    }
}
