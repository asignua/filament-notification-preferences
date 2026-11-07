<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Tests\Feature;

use Asignua\FilamentNotificationPreferences\Livewire\PreferencesMatrix;
use Asignua\FilamentNotificationPreferences\NotificationPreferencesPlugin;
use Asignua\FilamentNotificationPreferences\Pages\NotificationPreferencesPage;
use Asignua\FilamentNotificationPreferences\Tests\Support\Sandbox;
use Asignua\FilamentNotificationPreferences\Tests\TestCase;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Workbench\App\Models\User;

class AccessTest extends TestCase
{
    use Sandbox;

    public function test_any_signed_in_user_may_open_the_page_by_default(): void
    {
        $this->assertTrue(NotificationPreferencesPage::canAccess());

        $this->get('/admin/notification-preferences')->assertOk()->assertSee('New comment');
    }

    public function test_a_guest_is_sent_to_the_login(): void
    {
        auth()->logout();

        $this->get('/admin/notification-preferences')->assertRedirect();
    }

    public function test_a_defined_gate_that_says_no_closes_the_page_and_the_component(): void
    {
        Gate::define(NotificationPreferencesPlugin::GATE, fn (User $user): bool => false);

        $this->assertFalse(NotificationPreferencesPage::canAccess());
        $this->assertFalse(NotificationPreferencesPage::shouldRegisterNavigation());
        $this->get('/admin/notification-preferences')->assertForbidden();

        Livewire::test(PreferencesMatrix::class)->assertForbidden();
    }

    public function test_a_gate_that_says_yes_opens_it(): void
    {
        Gate::define(NotificationPreferencesPlugin::GATE, fn (User $user): bool => $user->email === auth()->user()->email);

        $this->get('/admin/notification-preferences')->assertOk();
    }

    public function test_the_gate_is_checked_again_on_save_not_only_on_mount(): void
    {
        $component = Livewire::test(PreferencesMatrix::class);

        Gate::define(NotificationPreferencesPlugin::GATE, fn (User $user): bool => false);

        $component->call('save')->assertForbidden();
    }

    public function test_the_visible_condition_narrows_access(): void
    {
        $plugin = Filament::getPanel('admin')->getPlugin(NotificationPreferencesPlugin::ID);
        $visible = new \ReflectionProperty($plugin, 'visible');
        $visible->setValue($plugin, fn (User $user): bool => str_ends_with($user->email, '@staff.test'));

        try {
            $this->assertFalse(NotificationPreferencesPage::canAccess());

            auth()->user()->forceFill(['email' => 'me@staff.test']);
            $this->assertTrue(NotificationPreferencesPage::canAccess());
        } finally {
            $visible->setValue($plugin, true);
        }
    }

    public function test_the_visible_condition_cannot_widen_a_gate_that_says_no(): void
    {
        Gate::define(NotificationPreferencesPlugin::GATE, fn (User $user): bool => false);

        $this->assertFalse(NotificationPreferencesPlugin::make()->visible(true)->canManage(auth()->user()));
    }

    public function test_a_guest_has_no_access_to_the_component(): void
    {
        auth()->logout();

        $this->assertFalse(NotificationPreferencesPlugin::make()->canManage(null));
    }

    public function test_the_user_menu_carries_a_link(): void
    {
        $items = collect(Filament::getPanel('admin')->getUserMenuItems());

        $this->assertTrue($items->has('notification-preferences'));
    }
}
