<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Jobs;

use App\Services\Maintenance\Maintenance;
use App\Services\Maintenance\Settings;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Maintenance\Jobs\StartCronJob
 */
class StartCronJobTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertCronableRegistered(StartCronJob::class);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void {
        $maintenance = Mockery::mock(Maintenance::class);

        $maintenance
            ->shouldReceive('getSettings')
            ->once()
            ->andReturn(new Settings());
        $maintenance
            ->shouldReceive('enable')
            ->once()
            ->andReturn(true);

        ($this->app->make(StartCronJob::class))($maintenance);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeNoSettings(): void {
        $maintenance = Mockery::mock(Maintenance::class);

        $maintenance
            ->shouldReceive('getSettings')
            ->once()
            ->andReturn(null);
        $maintenance
            ->shouldReceive('enable')
            ->never();

        ($this->app->make(StartCronJob::class))($maintenance);
    }
}
