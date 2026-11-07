<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class NotificationPreferencesServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-notification-preferences';

    public function configurePackage(Package $package): void
    {
        // Translations live in resources/lang/<locale>/filament-notification-preferences.php and are read as
        // `__('filament-notification-preferences::filament-notification-preferences.<key>')`. Publish tag: `filament-notification-preferences-translations`.
        $package->name(static::$name)
            ->hasTranslations()
            ->hasViews();

        // Add a config file only when the plugin really has options: create config/filament-notification-preferences.php and
        // chain `->hasConfigFile()` here (publish tag `filament-notification-preferences-config`). Prefer fluent setters on the Plugin.
    }
}
