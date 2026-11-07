<?php

declare(strict_types=1);

use Asignua\FilamentNotificationPreferences\Models\NotificationPreference;

return [
    /*
     * Database connection and table of the stored preferences. `null` = the default connection.
     */
    'connection' => env('NOTIFICATION_PREFERENCES_CONNECTION'),

    'table' => env('NOTIFICATION_PREFERENCES_TABLE', 'notification_preferences'),

    /*
     * Swap in your own model (it must extend the package model).
     */
    'model' => NotificationPreference::class,

    /*
     * The preferences of one recipient are read in ONE query and remembered here between requests and queue jobs.
     * The entry is dropped when the user saves the page. Only the stored deviations are cached, never the
     * defaults, so changing a default in code takes effect at once.
     *
     * The `array` and `null` drivers are never used for this: an array store lives and dies with one process,
     * so a long-running queue worker would keep an old answer after the web request changed it.
     * `store` = null means the default cache store.
     */
    'cache' => [
        'enabled' => (bool) env('NOTIFICATION_PREFERENCES_CACHE', true),
        'store' => env('NOTIFICATION_PREFERENCES_CACHE_STORE'),
        'ttl' => (int) env('NOTIFICATION_PREFERENCES_CACHE_TTL', 3600),
    ],
];
