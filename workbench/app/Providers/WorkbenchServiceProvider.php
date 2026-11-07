<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Asignua\FilamentNotificationPreferences\NotificationPreferences;
use Asignua\FilamentNotificationPreferences\Support\NotificationType;
use Illuminate\Support\ServiceProvider;
use Workbench\App\Channels\SinkChannel;
use Workbench\App\Notifications\InvoicePaid;
use Workbench\App\Notifications\MentionedYou;
use Workbench\App\Notifications\NewComment;
use Workbench\App\Notifications\SecurityAlert;
use Workbench\App\Notifications\WeeklyDigest;

/**
 * The demo's notification types, registered the way a host does it: from a service provider, so a queue worker
 * (which has no panel) knows them too.
 */
class WorkbenchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        NotificationPreferences::register(
            NotificationType::make(NewComment::class)
                ->key('comments.new')
                ->label('New comment')
                ->description('Someone commented on a record you follow.')
                ->group('Comments')
                ->channels(['mail', 'database']),
            NotificationType::make(MentionedYou::class)
                ->key('comments.mention')
                ->label('Mentions')
                ->description('Someone mentioned you.')
                ->group('Comments')
                ->channels(['mail', 'database'])
                ->default(['mail' => false, 'database' => true]),
            NotificationType::make(WeeklyDigest::class)
                ->key('digests.weekly')
                ->label('Weekly digest')
                ->description('A summary of the week, by email only.')
                ->group('Digests')
                ->channels(['mail'])
                ->default(false),
            NotificationType::make(InvoicePaid::class)
                ->key('billing.invoice-paid')
                ->label('Invoice paid')
                ->description('Sent when a customer pays one of your invoices.')
                ->group('Billing')
                ->channels(['mail', 'database', SinkChannel::class => 'SMS'])
                ->default([SinkChannel::class => false]),
            NotificationType::make(SecurityAlert::class)
                ->key('security.alert')
                ->label('Security alerts')
                ->description('Sign-ins from a new device.')
                ->group('Security')
                ->channels(['mail', 'database'])
                ->locked(),
        );
    }
}
