<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Tests;

use Asignua\FilamentNotificationPreferences\NotificationPreferencesServiceProvider;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Workbench\App\Providers\AdminPanelProvider;
use Workbench\App\Providers\WorkbenchServiceProvider;

abstract class BaseTestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            NotificationPreferencesServiceProvider::class,
            WorkbenchServiceProvider::class,
            AdminPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        // Deterministic: queued notifications run inline unless a test starts a real worker.
        $app['config']->set('queue.default', 'sync');
        [$name, $connection] = static::connectionSettings();
        $app['config']->set('database.default', $name);
        $app['config']->set('database.connections.'.$name, $connection);
        $app['config']->set('auth.providers.users.model', User::class);
    }

    /**
     * The database the suite runs on: SQLite in memory by default; `NPREF_DB=mariadb|pgsql` (+ `NPREF_DB_HOST`,
     * `NPREF_DB_PORT`) points the same suite at a real server, which is how the index lengths and the upsert are
     * checked on MariaDB 11.4 and Postgres 16. The connection carries the standard driver name and reads the
     * standard `DB_*` variables, so an out-of-process `queue:work` started with {@see self::childEnvironment()}
     * lands in the very same database.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    protected static function connectionSettings(): array
    {
        $driver = getenv('NPREF_DB') ?: 'sqlite';

        if ($driver === 'sqlite') {
            return ['sqlite', ['driver' => 'sqlite', 'database' => static::sqliteDatabase(), 'prefix' => '', 'foreign_key_constraints' => true]];
        }

        $name = $driver === 'mariadb' ? 'mariadb' : 'pgsql';

        return [$name, [
            'driver' => $name,
            'host' => getenv('NPREF_DB_HOST') ?: '127.0.0.1',
            'port' => getenv('NPREF_DB_PORT') ?: ($name === 'mariadb' ? '3306' : '5432'),
            'database' => 'npref',
            'username' => 'npref',
            'password' => 'secret',
            'charset' => $name === 'mariadb' ? 'utf8mb4' : 'utf8',
            'collation' => $name === 'mariadb' ? 'utf8mb4_unicode_ci' : null,
            'prefix' => '',
        ]];
    }

    protected static function sqliteDatabase(): string
    {
        return ':memory:';
    }

    /**
     * The environment a child process needs to use the same database as this test.
     *
     * @return array<string, string>
     */
    protected static function childEnvironment(): array
    {
        [$name, $config] = static::connectionSettings();

        return [
            'DB_CONNECTION' => $name,
            'DB_DATABASE' => (string) $config['database'],
            'DB_HOST' => (string) ($config['host'] ?? ''),
            'DB_PORT' => (string) ($config['port'] ?? ''),
            'DB_USERNAME' => (string) ($config['username'] ?? ''),
            'DB_PASSWORD' => (string) ($config['password'] ?? ''),
        ];
    }

    /**
     * In-memory SQLite is rebuilt for every test, so Testbench loads the workbench migrations each time. A real
     * server (or a file a child process shares) is migrated explicitly and ONCE, see {@see self::migrate()}: DDL
     * there commits implicitly, which would fight the per-test transaction and rollback Testbench does.
     */
    protected function defineDatabaseMigrations(): void
    {
        if (!static::migratesItself()) {
            $this->loadMigrationsFrom(__DIR__.'/../workbench/database/migrations');
        }
    }

    protected static function migratesItself(): bool
    {
        return (getenv('NPREF_DB') ?: 'sqlite') !== 'sqlite';
    }

    protected function migrate(): void
    {
        $this->artisan('migrate', ['--path' => dirname(__DIR__).'/workbench/database/migrations', '--realpath' => true, '--force' => true])->run();
    }
}
