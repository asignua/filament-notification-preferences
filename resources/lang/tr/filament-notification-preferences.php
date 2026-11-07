<?php

declare(strict_types=1);

return [
    'page' => [
        'title' => 'Bildirim tercihleri',
    ],
    'intro' => 'Hangi bildirimleri nerede alacağınızı seçin.',
    'column' => [
        'type' => 'Bildirim',
    ],
    'channels' => [
        'mail' => 'E-posta',
        'database' => 'Uygulama içi',
        'broadcast' => 'Gerçek zamanlı',
    ],
    'group' => [
        'general' => 'Genel',
    ],
    'enable_all' => 'Tümünü etkinleştir',
    'disable_all' => 'Tümünü devre dışı bırak',
    'enable_all_in' => ':group içindeki tümünü etkinleştir',
    'disable_all_in' => ':group içindeki tümünü devre dışı bırak',
    'locked' => 'Zorunlu',
    'locked_explanation' => 'Bu bildirim zorunludur ve her zaman gönderilir.',
    'unavailable' => 'Bu bildirim için kullanılamaz',
    'cell_label' => ':type, :channel',
    'save' => 'Tercihleri kaydet',
    'saved' => 'Tercihler kaydedildi',
    'empty' => 'Henüz yapılandırılacak bildirim yok.',
];
