<?php

declare(strict_types=1);

return [
    'page' => [
        'title' => 'Preferências de notificação',
    ],
    'intro' => 'Escolha quais notificações você recebe e onde.',
    'column' => [
        'type' => 'Notificação',
    ],
    'channels' => [
        'mail' => 'E-mail',
        'database' => 'No aplicativo',
        'broadcast' => 'Tempo real',
    ],
    'group' => [
        'general' => 'Geral',
    ],
    'enable_all' => 'Ativar tudo',
    'disable_all' => 'Desativar tudo',
    'enable_all_in' => 'Ativar tudo em :group',
    'disable_all_in' => 'Desativar tudo em :group',
    'locked' => 'Obrigatória',
    'locked_explanation' => 'Esta notificação é obrigatória e sempre enviada.',
    'unavailable' => 'Indisponível para esta notificação',
    'cell_label' => ':type, :channel',
    'save' => 'Salvar preferências',
    'saved' => 'Preferências salvas',
    'empty' => 'Ainda não há notificações para configurar.',
];
