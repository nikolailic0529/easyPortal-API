<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Jobs;

use App\Services\Maintenance\Maintenance;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Maintenance\Jobs\EnableCronJob
 */
class EnableCronJobTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(EnableCronJob::class);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void {
        $maintenance = Mockery::mock(Maintenance::class);

        $maintenance
            ->shouldReceive('enable')
            ->once()
            ->andReturn(true);

        ($this->app->make(EnableCronJob::class))($maintenance);
    }
}
