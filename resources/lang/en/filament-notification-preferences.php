<?php

declare(strict_types=1);

return [
    'page' => [
        'title' => 'Notification preferences',
    ],
    'intro' => 'Choose which notifications you receive and where.',
    'column' => [
        'type' => 'Notification',
    ],
    'channels' => [
        'mail' => 'Email',
        'database' => 'In-app',
        'broadcast' => 'Real-time',
    ],
    'group' => [
        'general' => 'General',
    ],
    'enable_all' => 'Enable all',
    'disable_all' => 'Disable all',
    'enable_all_in' => 'Enable all in :group',
    'disable_all_in' => 'Disable all in :group',
    'locked' => 'Required',
    'locked_explanation' => 'This notification is required and is always sent.',
    'unavailable' => 'Not available for this notification',
    'cell_label' => ':type, :channel',
    'save' => 'Save preferences',
    'saved' => 'Preferences saved',
    'empty' => 'There are no notifications to configure yet.',
];
