<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Jobs;

use App\Services\Maintenance\Maintenance;
use App\Services\Maintenance\Settings;
use Illuminate\Contracts\Config\Repository;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Maintenance\Jobs\CompleteCronJob
 */
class CompleteCronJobTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertCronableRegistered(CompleteCronJob::class);
    }

    public function testInvokeNoSettings(): void {
        $config = $this->app->make(Repository::class);
        $job    = Mockery::mock(CompleteCronJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();
        $job
            ->shouldReceive('notify')
            ->never();

        $maintenance = Mockery::mock(Maintenance::class);
        $maintenance
            ->shouldReceive('disable')
            ->once()
            ->andReturn(true);
        $maintenance
            ->shouldReceive('getSettings')
            ->once()
            ->andReturn(null);

        $job($config, $maintenance);
    }

    public function testInvokeSettingsNotifiedFalse(): void {
        $config = $this->app->make(Repository::class);
        $job    = Mockery::mock(CompleteCronJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();
        $job
            ->shouldReceive('notify')
            ->never();

        $maintenance = Mockery::mock(Maintenance::class);
        $maintenance
            ->shouldReceive('disable')
            ->once()
            ->andReturn(true);
        $maintenance
            ->shouldReceive('getSettings')
            ->once()
            ->andReturn(new Settings([
                'notified' => false,
            ]));

        $job($config, $maintenance);
    }

    public function testInvokeSettingsNotifiedTrue(): void {
        $config = $this->app->make(Repository::class);
        $job    = Mockery::mock(CompleteCronJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();
        $job
            ->shouldReceive('notify')
            ->once();

        $maintenance = Mockery::mock(Maintenance::class);
        $maintenance
            ->shouldReceive('disable')
            ->once()
            ->andReturn(true);
        $maintenance
            ->shouldReceive('getSettings')
            ->once()
            ->andReturn(new Settings([
                'notified' => true,
            ]));

        $job($config, $maintenance);
    }
}
