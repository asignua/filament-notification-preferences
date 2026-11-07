<?php

declare(strict_types=1);

namespace Workbench\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Workbench\App\Channels\SinkChannel;

/**
 * A demo notification: the plugin never touches a class like this one.
 */
class InvoicePaid extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', SinkChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('InvoicePaid')->line('InvoicePaid');
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return ['type' => 'InvoicePaid'];
    }
}
