<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Jobs;

use App\Services\Maintenance\Maintenance;
use App\Services\Maintenance\Settings;
use Illuminate\Contracts\Config\Repository;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Maintenance\Jobs\NotifyCronJob
 */
class NotifyCronJobTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertCronableRegistered(NotifyCronJob::class);
    }

    public function testInvoke(): void {
        $config = $this->app->make(Repository::class);
        $job    = Mockery::mock(NotifyCronJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();
        $job
            ->shouldReceive('notify')
            ->once();

        $maintenance = Mockery::mock(Maintenance::class);
        $maintenance
            ->shouldReceive('getSettings')
            ->once()
            ->andReturn(new Settings());
        $maintenance
            ->shouldReceive('markAsNotified')
            ->once()
            ->andReturn(true);

        $job($config, $maintenance);
    }

    public function testInvokeNoSettings(): void {
        $config = $this->app->make(Repository::class);
        $job    = Mockery::mock(NotifyCronJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();
        $job
            ->shouldReceive('notify')
            ->never();

        $maintenance = Mockery::mock(Maintenance::class);
        $maintenance
            ->shouldReceive('getSettings')
            ->once()
            ->andReturn(null);
        $maintenance
            ->shouldReceive('markAsNotified')
            ->never();

        $job($config, $maintenance);
    }
}
