<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Tests\Feature;

use Asignua\FilamentNotificationPreferences\NotificationPreferences;
use Asignua\FilamentNotificationPreferences\Tests\BaseTestCase;
use Asignua\FilamentNotificationPreferences\Tests\Support\Sandbox;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Workbench\App\Channels\SinkChannel;
use Workbench\App\Notifications\InvoicePaid;
use Workbench\App\Notifications\NewComment;

/**
 * The choice must apply to a notification that is ALREADY in the queue: a real `queue:work` process, started after
 * the user changed their mind, reads the choices at the moment of sending. Nothing is faked: the jobs live in the
 * database, the worker is a separate PHP process, the channels are the real database and mail ones.
 */
class QueueWorkerTest extends BaseTestCase
{
    use Sandbox;

    private ?string $sqliteFile = null;

    private string $sink = '';

    protected static function migratesItself(): bool
    {
        return true;
    }

    protected static function sqliteDatabase(): string
    {
        // A separate process cannot see ':memory:'. The name is stable per test run so that childEnvironment()
        // (called while the application boots) and the test body agree.
        return sys_get_temp_dir().'/npref-worker-'.getmypid().'.sqlite';
    }

    protected function setUp(): void
    {
        $file = static::sqliteDatabase();

        if ((getenv('NPREF_DB') ?: 'sqlite') === 'sqlite') {
            touch($file);
            $this->sqliteFile = $file;
        }

        parent::setUp();

        // Committed data, no wrapping transaction: the worker is another process and must see it. The database is a
        // throwaway one (a temp SQLite file or the disposable server), so plain `migrate` is enough and rows are
        // removed again in tearDown().
        $this->migrate();

        $this->sink = (string) tempnam(sys_get_temp_dir(), 'npref-sink');
        config(['queue.default' => 'database']);
    }

    protected function tearDown(): void
    {
        foreach (['jobs', 'failed_jobs', 'notifications', (string) config('filament-notification-preferences.table'), 'users'] as $table) {
            DB::table($table)->delete();
        }

        parent::tearDown();

        @unlink($this->sink);

        if ($this->sqliteFile !== null) {
            @unlink($this->sqliteFile);
        }
    }

    public function test_a_notification_queued_before_the_user_turned_it_off_is_not_sent_by_a_real_worker(): void
    {
        $turnedOff = $this->makeUser('Turned off');
        $untouched = $this->makeUser('Untouched');
        $sms = $this->makeUser('Wants SMS');

        // Queued while everybody still gets everything.
        foreach ([$turnedOff, $untouched, $sms] as $user) {
            $user->notify(new NewComment);
            $user->notify(new InvoicePaid);
        }

        $this->assertGreaterThanOrEqual(6, DB::table('jobs')->count(), 'the notifications are waiting in the queue');
        $this->assertSame(0, DB::table('notifications')->count(), 'nothing has been sent yet');

        // The users change their mind AFTER the jobs were queued.
        NotificationPreferences::for($turnedOff)->save([
            'comments.new' => ['mail' => false, 'database' => false],
        ]);
        NotificationPreferences::for($sms)->save([
            'billing.invoice-paid' => [SinkChannel::class => true],
        ]);

        $worker = $this->runWorker();

        $this->assertSame(0, $worker->getExitCode(), $worker->getErrorOutput().$worker->getOutput());
        $this->assertSame(0, DB::table('jobs')->count(), 'the worker drained the queue');
        $this->assertSame(0, DB::table('failed_jobs')->count(), 'and nothing failed');

        // In-app channel: the user who turned the type off got no row, the others did.
        $this->assertSame(0, $this->inAppFor($turnedOff, NewComment::class));
        $this->assertSame(1, $this->inAppFor($untouched, NewComment::class));
        $this->assertSame(1, $this->inAppFor($sms, NewComment::class));

        // Mail channel (the worker logged what it sent): one NewComment mail less than recipients.
        $log = $worker->getErrorOutput().$worker->getOutput();
        $this->assertSame(2, substr_count($log, 'Subject: NewComment'), 'two of three recipients got the comment mail');
        $this->assertSame(3, substr_count($log, 'Subject: InvoicePaid'), 'a type nobody touched is still mailed to all');

        // Custom channel by class name: off by default, so only the user who turned it on is reached.
        $sent = array_filter(explode("\n", (string) file_get_contents($this->sink)));
        $this->assertSame([InvoicePaid::class.'|'.$sms->getKey()], array_values($sent));
    }

    public function test_a_type_turned_back_on_is_sent_by_the_worker_too(): void
    {
        $user = $this->makeUser();
        NotificationPreferences::for($user)->save(['comments.new' => ['mail' => false, 'database' => false]]);

        $user->notify(new NewComment);
        $this->assertSame(0, DB::table('notifications')->count());

        // Off when queued, on again before the worker runs: the choice at sending time wins.
        NotificationPreferences::for($user)->save(['comments.new' => ['mail' => true, 'database' => true]]);

        $this->assertSame(0, $this->runWorker()->getExitCode());
        $this->assertSame(1, $this->inAppFor($user, NewComment::class));
    }

    private function inAppFor(object $user, string $class): int
    {
        return DB::table('notifications')->where('notifiable_id', $user->getKey())->where('type', $class)->count();
    }

    private function runWorker(): Process
    {
        $plugin = dirname(__DIR__, 2);

        $process = new Process(
            [PHP_BINARY, $plugin.'/vendor/bin/testbench', 'queue:work', 'database', '--stop-when-empty', '--tries=1', '--sleep=0'],
            $plugin,
            array_merge(static::childEnvironment(), [
                'QUEUE_CONNECTION' => 'database',
                'MAIL_MAILER' => 'log',
                'MAIL_LOG_CHANNEL' => 'stderr',
                'LOG_CHANNEL' => 'stderr',
                'LOG_LEVEL' => 'debug',
                'CACHE_STORE' => 'array',
                'NPREF_SINK' => $this->sink,
            ]),
            null,
            120,
        );
        $process->run();

        return $process;
    }
}
