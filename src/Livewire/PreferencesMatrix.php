<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Livewire;

use Asignua\FilamentNotificationPreferences\NotificationPreferences;
use Asignua\FilamentNotificationPreferences\NotificationPreferencesPlugin;
use Asignua\FilamentNotificationPreferences\Support\MatrixLayout;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

/**
 * The type x channel matrix of the signed-in user. The page renders it; embed it in a profile page the same way:
 * `@livewire(\Asignua\FilamentNotificationPreferences\Livewire\PreferencesMatrix::class)`.
 *
 * Every public method re-checks access and works on the signed-in user, never on an id from the request.
 */
class PreferencesMatrix extends Component
{
    /** @var array<string, array<string, bool>> type id => channel id => on */
    public array $matrix = [];

    public function mount(): void
    {
        $this->load();
    }

    public function save(): void
    {
        $recipient = $this->recipient();

        NotificationPreferences::for($recipient)->save($this->layout()->fromIds($this->matrix));

        $this->load();

        Notification::make()
            ->success()
            ->title(__('filament-notification-preferences::filament-notification-preferences.saved'))
            ->send();
    }

    /**
     * Sets every unlocked channel of a group on or off in the form; nothing is stored until "Save".
     */
    public function setGroup(string $group, bool $enabled): void
    {
        $this->recipient();

        $this->matrix = $this->layout()->withGroup($this->matrix, $group, $enabled);
    }

    public function render(): View
    {
        $this->recipient();

        return view('filament-notification-preferences::livewire.preferences-matrix', ['layout' => $this->layout()]);
    }

    private function load(): void
    {
        $layout = $this->layout();

        $this->matrix = $layout->toIds(NotificationPreferences::for($this->recipient())->matrix());
    }

    private function layout(): MatrixLayout
    {
        return MatrixLayout::build(NotificationPreferences::types());
    }

    private function recipient(): Model
    {
        $user = NotificationPreferencesPlugin::currentUser();

        abort_unless(NotificationPreferencesPlugin::resolve()->canManage($user), 403);
        abort_unless($user instanceof Model, 403);

        return $user;
    }
}
