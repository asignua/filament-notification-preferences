<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Listeners;

use Asignua\FilamentNotificationPreferences\NotificationPreferences;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Events\NotificationSending;
use Throwable;

/**
 * Stops a notification for one channel when its recipient turned that type + channel off.
 *
 * `NotificationSending` fires at the moment of sending, also inside the queue worker, so a choice made after a
 * notification was queued is honoured. Returning `false` cancels this one channel; returning nothing lets the
 * other listeners and the send continue.
 *
 * It only ever says no for a recipient that is an Eloquent model, for a declared type, an unlocked one and a
 * channel the type offers. An anonymous route (`Notification::route()`), an unknown notification and any error
 * all let the notification through.
 */
final class SuppressDisabledNotifications
{
    public function handle(NotificationSending $event): ?bool
    {
        try {
            if (!$event->notifiable instanceof Model) {
                return null;
            }

            return NotificationPreferences::for($event->notifiable)->allows($event->notification, $event->channel) ? null : false;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
