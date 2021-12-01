<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Commands;

use App\Services\Maintenance\Maintenance;
use App\Services\Maintenance\Settings;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Maintenance\Commands\Stop
 */
class StopTest extends TestCase {
    /**
     * @covers ::handle
     */
    public function testHandleSuccessNoWait(): void {
        $this->override(Maintenance::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('stop')
                ->with(false)
                ->once()
                ->andReturn(true);
        });

        $this
            ->artisan('ep:data-maintenance-stop')
            ->assertSuccessful()
            ->expectsOutput('Done.');
    }

    /**
     * @covers ::handle
     */
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
            ->artisan('ep:data-maintenance-stop', [
                '--wait' => true,
            ])
            ->assertSuccessful()
            ->expectsOutput('Done.');
    }

    /**
     * @covers ::handle
     */
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
            ->artisan('ep:data-maintenance-stop', [
                '--force' => true,
            ])
            ->assertSuccessful()
            ->expectsOutput('Done.');
    }

    /**
     * @covers ::handle
     */
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
            ->artisan('ep:data-maintenance-stop', [
                '--force' => true,
                '--wait'  => true,
            ])
            ->assertFailed()
            ->expectsOutput('Failed.');
    }

    /**
     * @covers ::handle
     */
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
            ->artisan('ep:data-maintenance-stop', [
                '--wait'  => true,
            ])
            ->assertFailed()
            ->expectsOutput('Failed.');
    }
}
