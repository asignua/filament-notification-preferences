<?php

declare(strict_types=1);

return [
    'page' => [
        'title' => 'Preferenze di notifica',
    ],
    'intro' => 'Scegli quali notifiche ricevere e dove.',
    'column' => [
        'type' => 'Notifica',
    ],
    'channels' => [
        'mail' => 'E-mail',
        'database' => 'Nell\'app',
        'broadcast' => 'Tempo reale',
    ],
    'group' => [
        'general' => 'Generale',
    ],
    'enable_all' => 'Attiva tutto',
    'disable_all' => 'Disattiva tutto',
    'enable_all_in' => 'Attiva tutto in :group',
    'disable_all_in' => 'Disattiva tutto in :group',
    'locked' => 'Obbligatoria',
    'locked_explanation' => 'Questa notifica è obbligatoria e viene sempre inviata.',
    'unavailable' => 'Non disponibile per questa notifica',
    'cell_label' => ':type, :channel',
    'save' => 'Salva preferenze',
    'saved' => 'Preferenze salvate',
    'empty' => 'Non ci sono ancora notifiche da configurare.',
];
