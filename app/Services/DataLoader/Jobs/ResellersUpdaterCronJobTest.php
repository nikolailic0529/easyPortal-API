<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Jobs\JobsCleanupCronJob;
use App\Models\Job;
use App\Models\Organization;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

use function sprintf;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Jobs\ResellersUpdaterCronJob
 */
class ResellersUpdaterCronJobTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(ResellersUpdaterCronJob::class);
    }

    /**
     * @covers ::handle
     */
    public function testHandle(): void {
        // Prepare
        Queue::fake();

        $d = Date::now();
        $a = Organization::factory()->create(['updated_at' => $d->subWeek()]);

        Organization::factory()->create(['updated_at' => $d]);
        Organization::factory()->create(['updated_at' => $d->addWeek()]);

        // Settings
        Config::set(sprintf('queue.queueables.%s', ResellersUpdaterCronJob::class), [
            'settings' => [
                'expire' => '5 days',
            ],
        ]);

        // Run
        $this->app->call([$this->app->make(ResellersUpdaterCronJob::class), 'handle']);

        // Test
        Queue::assertPushed(ResellerUpdate::class, 1);
        Queue::assertPushed(ResellerUpdate::class, static function (ResellerUpdate $job) use ($a): bool {
            return $job->getResellerId() === $a->getKey();
        });

        // Second run should not emit new jobs
        $this->app->call([$this->app->make(ResellersUpdaterCronJob::class), 'handle']);

        Queue::assertPushed(ResellerUpdate::class, 1);
    }
}
