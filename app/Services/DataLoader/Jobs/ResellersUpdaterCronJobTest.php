<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Reseller;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Client\QueryIterator;
use App\Services\DataLoader\Factories\ResellerFactory;
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
        $known    = new Company(['id' => $this->faker->uuid]);
        $unknown  = new Company(['id' => $this->faker->uuid]);
        $creator  = static function (Company $company) {
            return Reseller::factory()->make(['id' => $company->id]);
        };
        $iterator = Mockery::mock(QueryIterator::class);
        $iterator
            ->shouldReceive('getIterator')
            ->once()
            ->andReturnUsing(static function () use ($known, $unknown): Generator {
                yield from [$known, $unknown];
            });
        $iterator
            ->shouldReceive('each')
            ->once()
            ->andReturnSelf();

        $client = Mockery::mock(Client::class);
        $client
            ->shouldReceive('getResellers')
            ->withArgs(static function (DateTimeInterface|null $from) use ($expire): bool {
                return Date::now()->sub($expire)->equalTo($from);
            })
            ->once()
            ->andReturn($iterator);

        $factory = Mockery::mock(ResellerFactory::class);
        $factory->makePartial();
        $factory
            ->shouldReceive('find')
            ->with($known)
            ->once()
            ->andReturnUsing($creator);
        $factory
            ->shouldReceive('find')
            ->with($unknown)
            ->once()
            ->andReturn(null);
        $factory
            ->shouldReceive('create')
            ->with($unknown)
            ->andReturnUsing($creator);

        $this->app->bind(Client::class, static function () use ($client): Client {
            return $client;
        });
        $this->app->bind(ResellerFactory::class, static function () use ($factory) {
            return $factory;
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
        Queue::assertPushed(ResellerUpdate::class, 2);
        Queue::assertPushed(ResellerUpdate::class, static function (ResellerUpdate $job) use ($known): bool {
            return $job->getResellerId() === $known->id;
        });
        Queue::assertPushed(ResellerUpdate::class, static function (ResellerUpdate $job) use ($unknown): bool {
            return $job->getResellerId() === $unknown->id;
        });
    }
}
