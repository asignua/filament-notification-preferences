<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Tests;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Workbench\App\Models\User;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase {
        refreshTestDatabase as protected refreshInMemoryDatabase;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel('admin');
    }

    protected function refreshTestDatabase(): void
    {
        if (!static::migratesItself()) {
            $this->refreshInMemoryDatabase();

            return;
        }

        // A real server: schema once (a no-op when it is there), then every test in a rolled-back transaction.
        $this->migrate();
        $this->beginDatabaseTransaction();
    }
}
