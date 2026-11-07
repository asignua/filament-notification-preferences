<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences;

use Asignua\FilamentNotificationPreferences\Listeners\SuppressDisabledNotifications;
use Asignua\FilamentNotificationPreferences\Livewire\PreferencesMatrix;
use Asignua\FilamentNotificationPreferences\Support\PreferenceStore;
use Asignua\FilamentNotificationPreferences\Support\TypeRegistry;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class NotificationPreferencesServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-notification-preferences';

    public const string PACKAGE = 'asignua/filament-notification-preferences';

    public const string STYLESHEET = 'filament-notification-preferences';

    public function configurePackage(Package $package): void
    {
        // Translations: `__('filament-notification-preferences::filament-notification-preferences.<key>')`,
        // publish tag `filament-notification-preferences-translations`.
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews()
            ->hasMigration('create_notification_preferences_table');
    }

    public function packageRegistered(): void
    {
        // The declared types: filled at boot, then only read.
        $this->app->singleton(TypeRegistry::class);

        // SCOPED, not singleton: its memo of a recipient's choices must die with the request or the queue job,
        // otherwise Octane and `queue:work` would keep answering with a choice the user has since changed.
        $this->app->scoped(PreferenceStore::class);
    }

    public function packageBooted(): void
    {
        Livewire::component('filament-notification-preferences-matrix', PreferencesMatrix::class);

        FilamentAsset::register([
            Css::make(self::STYLESHEET, __DIR__.'/../resources/dist/filament-notification-preferences.css'),
        ], self::PACKAGE);

        Event::listen(NotificationSending::class, SuppressDisabledNotifications::class);
    }
}
