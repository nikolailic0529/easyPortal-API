<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Client\QueryIterator;
use App\Services\DataLoader\Schema\Company;
use DateTimeInterface;
use Generator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;
use Mockery;
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
        Date::setTestNow('2021-06-10T00:00:00.000+00:00');

        // Client
        $expire   = 'P5D';
        $company  = new Company(['id' => $this->faker->uuid]);
        $iterator = Mockery::mock(QueryIterator::class);
        $iterator
            ->shouldReceive('getIterator')
            ->once()
            ->andReturnUsing(static function () use ($company): Generator {
                yield from [$company];
            });

        $client = Mockery::mock(Client::class);
        $client
            ->shouldReceive('getResellers')
            ->withArgs(static function (DateTimeInterface|null $from) use ($expire): bool {
                return Date::now()->sub($expire)->equalTo($from);
            })
            ->once()
            ->andReturn($iterator);

        $this->app->bind(Client::class, static function () use ($client): Client {
            return $client;
        });

        // Settings
        $this->setQueueableConfig(ResellersUpdaterCronJob::class, [
            'settings' => [
                'expire' => $expire,
            ],
        ]);

        // Run
        $this->app->call([$this->app->make(ResellersUpdaterCronJob::class), 'handle']);

        // Test
        Queue::assertPushed(ResellerUpdate::class, 1);
        Queue::assertPushed(ResellerUpdate::class, static function (ResellerUpdate $job) use ($company): bool {
            return $job->getResellerId() === $company->id;
        });
    }
}
