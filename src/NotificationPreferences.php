<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences;

use Asignua\FilamentNotificationPreferences\Support\NotificationType;
use Asignua\FilamentNotificationPreferences\Support\PreferenceStore;
use Asignua\FilamentNotificationPreferences\Support\TypeRegistry;
use Asignua\FilamentNotificationPreferences\Support\UserPreferences;
use Illuminate\Database\Eloquent\Model;

/**
 * The entry point for code.
 *
 *     // AppServiceProvider::boot()
 *     NotificationPreferences::register(
 *         NotificationType::make(InvoicePaid::class)->label('Invoice paid')->group('Billing'),
 *     );
 *
 *     NotificationPreferences::for($user)->allows(InvoicePaid::class, 'mail');
 */
final class NotificationPreferences
{
    public static function register(NotificationType ...$types): void
    {
        app(TypeRegistry::class)->register(...$types);
    }

    public static function for(Model $notifiable): UserPreferences
    {
        return new UserPreferences($notifiable, app(TypeRegistry::class), app(PreferenceStore::class));
    }

    public static function types(): TypeRegistry
    {
        return app(TypeRegistry::class);
    }
}
