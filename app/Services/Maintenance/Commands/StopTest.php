<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Commands;

use App\Services\Maintenance\Maintenance;
use App\Services\Maintenance\Settings;
use Illuminate\Contracts\Console\Kernel;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Maintenance\Commands\Stop
 */
class StopTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testCommand(): void {
        self::assertCommandDescription('ep:maintenance-stop');
    }

    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertArrayHasKey('ep:maintenance-stop', $this->app->make(Kernel::class)->all());
    }

    public function testHandleSuccessNoWait(): void {
        $this->override(Maintenance::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('stop')
                ->with(false)
                ->once()
                ->andReturn(true);
        });

        $this
            ->artisan('ep:maintenance-stop')
            ->assertSuccessful()
            ->expectsOutput('Done.');
    }

    public function testHandleSuccessWait(): void {
        $this->override(Maintenance::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('stop')
                ->with(false)
                ->once()
                ->andReturn(true);
            $mock
                ->shouldReceive('getSettings')
                ->twice()
                ->andReturn(new Settings(), null);
        });

        $this
            ->artisan('ep:maintenance-stop', [
                '--wait' => true,
            ])
            ->assertSuccessful()
            ->expectsOutput('Done.');
    }

    public function testHandleSuccessForce(): void {
        $this->override(Maintenance::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('stop')
                ->with(true)
                ->once()
                ->andReturn(true);
            $mock
                ->shouldReceive('getSettings')
                ->never();
        });

        $this
            ->artisan('ep:maintenance-stop', [
                '--force' => true,
            ])
            ->assertSuccessful()
            ->expectsOutput('Done.');
    }

    public function testHandleFailedForce(): void {
        $this->override(Maintenance::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('stop')
                ->with(true)
                ->once()
                ->andReturn(false);
            $mock
                ->shouldReceive('getSettings')
                ->never();
        });

        $this
            ->artisan('ep:maintenance-stop', [
                '--force' => true,
                '--wait'  => true,
            ])
            ->assertFailed()
            ->expectsOutput('Failed.');
    }

    public function testHandleFailed(): void {
        $this->override(Maintenance::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('stop')
                ->with(false)
                ->once()
                ->andReturn(false);
            $mock
                ->shouldReceive('getSettings')
                ->never();
        });

        $this
            ->artisan('ep:maintenance-stop', [
                '--wait'  => true,
            ])
            ->assertFailed()
            ->expectsOutput('Failed.');
    }
}
