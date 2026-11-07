<?php

declare(strict_types=1);

return [
    'page' => [
        'title' => 'Meldingsvoorkeuren',
    ],
    'intro' => 'Kies welke meldingen je ontvangt en waar.',
    'column' => [
        'type' => 'Melding',
    ],
    'channels' => [
        'mail' => 'E-mail',
        'database' => 'In de app',
        'broadcast' => 'Realtime',
    ],
    'group' => [
        'general' => 'Algemeen',
    ],
    'enable_all' => 'Alles inschakelen',
    'disable_all' => 'Alles uitschakelen',
    'enable_all_in' => 'Alles inschakelen in :group',
    'disable_all_in' => 'Alles uitschakelen in :group',
    'locked' => 'Verplicht',
    'locked_explanation' => 'Deze melding is verplicht en wordt altijd verzonden.',
    'unavailable' => 'Niet beschikbaar voor deze melding',
    'cell_label' => ':type, :channel',
    'save' => 'Voorkeuren opslaan',
    'saved' => 'Voorkeuren opgeslagen',
    'empty' => 'Er zijn nog geen meldingen om in te stellen.',
];
