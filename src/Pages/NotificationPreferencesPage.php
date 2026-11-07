<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Pages;

use Asignua\FilamentNotificationPreferences\NotificationPreferencesPlugin;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class NotificationPreferencesPage extends Page
{
    protected string $view = 'filament-notification-preferences::pages.notification-preferences';

    public static function getSlug(?Panel $panel = null): string
    {
        return NotificationPreferencesPlugin::resolve()->getSlug();
    }

    public static function canAccess(): bool
    {
        return NotificationPreferencesPlugin::resolve()->canManage(NotificationPreferencesPlugin::currentUser());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return NotificationPreferencesPlugin::resolve()->shouldRegisterNavigation() && static::canAccess();
    }

    public static function getNavigationLabel(): string|Htmlable
    {
        return NotificationPreferencesPlugin::resolve()->getNavigationLabel() ?? static::pageTitle();
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return NotificationPreferencesPlugin::resolve()->getNavigationGroup();
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return NotificationPreferencesPlugin::resolve()->getNavigationIcon();
    }

    public static function getNavigationSort(): ?int
    {
        return NotificationPreferencesPlugin::resolve()->getNavigationSort();
    }

    public function getTitle(): string|Htmlable
    {
        return static::pageTitle();
    }

    protected static function pageTitle(): string
    {
        return (string) __('filament-notification-preferences::filament-notification-preferences.page.title');
    }
}
