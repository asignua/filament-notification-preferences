<?php

declare(strict_types=1);

return [
    'page' => [
        'title' => 'Benachrichtigungseinstellungen',
    ],
    'intro' => 'Wählen Sie, welche Benachrichtigungen Sie erhalten und wo.',
    'column' => [
        'type' => 'Benachrichtigung',
    ],
    'channels' => [
        'mail' => 'E-Mail',
        'database' => 'In der App',
        'broadcast' => 'Echtzeit',
    ],
    'group' => [
        'general' => 'Allgemein',
    ],
    'enable_all' => 'Alle aktivieren',
    'disable_all' => 'Alle deaktivieren',
    'enable_all_in' => 'Alle in :group aktivieren',
    'disable_all_in' => 'Alle in :group deaktivieren',
    'locked' => 'Erforderlich',
    'locked_explanation' => 'Diese Benachrichtigung ist erforderlich und wird immer gesendet.',
    'unavailable' => 'Für diese Benachrichtigung nicht verfügbar',
    'cell_label' => ':type, :channel',
    'save' => 'Einstellungen speichern',
    'saved' => 'Einstellungen gespeichert',
    'empty' => 'Es gibt noch keine Benachrichtigungen zum Konfigurieren.',
];
