<?php

declare(strict_types=1);

return [
    'page' => [
        'title' => 'Preferencje powiadomień',
    ],
    'intro' => 'Wybierz, jakie powiadomienia otrzymujesz i gdzie.',
    'column' => [
        'type' => 'Powiadomienie',
    ],
    'channels' => [
        'mail' => 'E-mail',
        'database' => 'W aplikacji',
        'broadcast' => 'Czas rzeczywisty',
    ],
    'group' => [
        'general' => 'Ogólne',
    ],
    'enable_all' => 'Włącz wszystkie',
    'disable_all' => 'Wyłącz wszystkie',
    'enable_all_in' => 'Włącz wszystkie w grupie :group',
    'disable_all_in' => 'Wyłącz wszystkie w grupie :group',
    'locked' => 'Wymagane',
    'locked_explanation' => 'To powiadomienie jest wymagane i zawsze wysyłane.',
    'unavailable' => 'Niedostępne dla tego powiadomienia',
    'cell_label' => ':type, :channel',
    'save' => 'Zapisz preferencje',
    'saved' => 'Preferencje zapisane',
    'empty' => 'Nie ma jeszcze powiadomień do skonfigurowania.',
];
