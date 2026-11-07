<?php

declare(strict_types=1);

namespace Workbench\App\Channels;

use Illuminate\Notifications\Notification;

/**
 * A custom channel (declared by its class name): appends one line to the file named by NPREF_SINK. It stands in
 * for SMS and lets an out-of-process test see what a queue worker really sent.
 */
class SinkChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $sink = getenv('NPREF_SINK');

        if (is_string($sink) && $sink !== '') {
            file_put_contents($sink, $notification::class.'|'.$notifiable->getKey()."\n", FILE_APPEND | LOCK_EX);
        }
    }
}
