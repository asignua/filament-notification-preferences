<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Workbench\App\Models\User;

/**
 * Small helpers shared by the delivery tests.
 */
trait Sandbox
{
    protected function makeUser(string $name = 'Ann'): User
    {
        return User::factory()->create(['name' => $name]);
    }

    /**
     * Mail goes to the array transport; its messages are what was really handed to the mailer.
     */
    protected function useArrayMailer(): void
    {
        config(['mail.default' => 'array']);
        Mail::purge('array');
    }

    protected function sentMailCount(): int
    {
        $transport = Mail::mailer('array')->getSymfonyTransport();

        return $transport instanceof TransportInterface && method_exists($transport, 'messages') ? $transport->messages()->count() : 0;
    }

    protected function inAppCount(User $user): int
    {
        return $user->notifications()->count();
    }

    /**
     * @return list<string> the SQL of every query on the preferences table while $callback runs
     */
    protected function preferenceQueries(callable $callback): array
    {
        $table = (string) config('filament-notification-preferences.table');
        $queries = [];

        DB::listen(function ($query) use (&$queries, $table): void {
            if (str_contains($query->sql, $table)) {
                $queries[] = $query->sql;
            }
        });

        $callback();

        return $queries;
    }
}
