<?php

declare(strict_types=1);

return [
    'page' => [
        'title' => 'Preferencias de notificaciones',
    ],
    'intro' => 'Elige qué notificaciones recibes y dónde.',
    'column' => [
        'type' => 'Notificación',
    ],
    'channels' => [
        'mail' => 'Correo',
        'database' => 'En la aplicación',
        'broadcast' => 'Tiempo real',
    ],
    'group' => [
        'general' => 'General',
    ],
    'enable_all' => 'Activar todo',
    'disable_all' => 'Desactivar todo',
    'enable_all_in' => 'Activar todo en :group',
    'disable_all_in' => 'Desactivar todo en :group',
    'locked' => 'Obligatoria',
    'locked_explanation' => 'Esta notificación es obligatoria y siempre se envía.',
    'unavailable' => 'No disponible para esta notificación',
    'cell_label' => ':type, :channel',
    'save' => 'Guardar preferencias',
    'saved' => 'Preferencias guardadas',
    'empty' => 'Todavía no hay notificaciones que configurar.',
];
