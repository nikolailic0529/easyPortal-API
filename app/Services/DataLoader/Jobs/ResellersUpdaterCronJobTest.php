<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Reseller;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

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
        $a = Reseller::factory()->create(['updated_at' => $d->subWeek()]);

        Reseller::factory()->create(['updated_at' => $d]);
        Reseller::factory()->create(['updated_at' => $d->addWeek()]);

        // Settings
        $this->setQueueableConfig(ResellersUpdaterCronJob::class, [
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
