<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Tests\Support;

use Asignua\FilamentNotificationPreferences\Models\NotificationPreference;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

/**
 * A preference model whose every query fails: stands in for "the choices cannot be read".
 */
class ThrowingPreference extends NotificationPreference
{
    /**
     * @return Builder<static>
     */
    public function newQuery(): Builder
    {
        throw new RuntimeException('The preferences cannot be read.');
    }
}
