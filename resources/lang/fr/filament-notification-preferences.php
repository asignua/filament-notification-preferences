<?php

declare(strict_types=1);

return [
    'page' => [
        'title' => 'Préférences de notification',
    ],
    'intro' => 'Choisissez les notifications que vous recevez et où.',
    'column' => [
        'type' => 'Notification',
    ],
    'channels' => [
        'mail' => 'E-mail',
        'database' => 'Dans l\'application',
        'broadcast' => 'Temps réel',
    ],
    'group' => [
        'general' => 'Général',
    ],
    'enable_all' => 'Tout activer',
    'disable_all' => 'Tout désactiver',
    'enable_all_in' => 'Tout activer dans :group',
    'disable_all_in' => 'Tout désactiver dans :group',
    'locked' => 'Obligatoire',
    'locked_explanation' => 'Cette notification est obligatoire et toujours envoyée.',
    'unavailable' => 'Indisponible pour cette notification',
    'cell_label' => ':type, :channel',
    'save' => 'Enregistrer les préférences',
    'saved' => 'Préférences enregistrées',
    'empty' => 'Aucune notification à configurer pour le moment.',
];
