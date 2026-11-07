<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One deviation from a type's default: "this recipient wants (or does not want) this type on this channel".
 *
 * @property int $id
 * @property string $notifiable_type
 * @property string $notifiable_id
 * @property string $type_key
 * @property string $channel
 * @property bool $enabled
 */
class NotificationPreference extends Model
{
    protected $guarded = [];

    public function getConnectionName(): ?string
    {
        $connection = config('filament-notification-preferences.connection');

        return is_string($connection) && $connection !== '' ? $connection : null;
    }

    public function getTable(): string
    {
        return (string) config('filament-notification-preferences.table', 'notification_preferences');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }
}
