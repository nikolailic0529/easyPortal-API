<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Customer;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Jobs\CustomersUpdaterCronJob
 */
class CustomersUpdaterCronJobTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(CustomersUpdaterCronJob::class);
    }

    /**
     * @covers ::handle
     */
    public function testHandle(): void {
        // Prepare
        Queue::fake();

        $d = Date::now();
        $a = Customer::factory()->create(['updated_at' => $d->subWeek()]);

        Customer::factory()->create(['updated_at' => $d]);
        Customer::factory()->create(['updated_at' => $d->addWeek()]);

        // Settings
        $this->setQueueableConfig(CustomersUpdaterCronJob::class, [
            'settings' => [
                'expire' => '5 days',
            ],
        ]);

        // Run
        $this->app->call([$this->app->make(CustomersUpdaterCronJob::class), 'handle']);

        // Test
        Queue::assertPushed(CustomerUpdate::class, 1);
        Queue::assertPushed(CustomerUpdate::class, static function (CustomerUpdate $job) use ($a): bool {
            return $job->getCustomerId() === $a->getKey();
        });

        // Second run should not emit new jobs
        $this->app->call([$this->app->make(CustomersUpdaterCronJob::class), 'handle']);

        Queue::assertPushed(CustomerUpdate::class, 1);
    }
}
