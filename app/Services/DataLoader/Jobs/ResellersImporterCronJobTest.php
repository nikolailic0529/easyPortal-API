<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Reseller;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Client\QueryIterator;
use App\Services\DataLoader\DataLoaderService;
use App\Services\DataLoader\Factories\ResellerFactory;
use App\Services\DataLoader\Schema\Company;
use Generator;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

use function in_array;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Jobs\ResellersImporterCronJob
 */
class ResellersImporterCronJobTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(ResellersImporterCronJob::class);
    }

    /**
     * @covers ::handle
     */
    public function testHandle(): void {
        // Fake
        Queue::fake();

        // Prepare
        $o       = Reseller::factory()->create();
        $a       = new Company(['id' => $o->getKey()]);
        $b       = new Company(['id' => $this->faker->uuid]);
        $c       = new Company(['id' => $this->faker->uuid]);
        $items   = [$a, $b, $c];
        $closure = static function (Company $company) {
            return Reseller::factory()->make([
                'id' => $company->id,
            ]);
        };

        $factory = Mockery::mock(ResellerFactory::class);
        $factory->makePartial();

        $factory
            ->shouldReceive('find')
            ->with($a)
            ->once()
            ->andReturn($o);
        $factory
            ->shouldReceive('create')
            ->with($a)
            ->never();

        $factory
            ->shouldReceive('find')
            ->with($b)
            ->once()
            ->andReturn(null);
        $factory
            ->shouldReceive('create')
            ->with($b)
            ->once()
            ->andReturnUsing($closure);

        $factory
            ->shouldReceive('find')
            ->with($c)
            ->once()
            ->andReturn(null);
        $factory
            ->shouldReceive('create')
            ->with($c)
            ->once()
            ->andReturnUsing($closure);

        $client = Mockery::mock(Client::class);
        $client
            ->shouldReceive('getResellers')
            ->once()
            ->andReturnUsing(static function () use ($items): QueryIterator {
                return new class($items) extends QueryIterator {
                    /**
                     * @var array<\App\Services\DataLoader\Schema\Company>
                     */
                    private array $items;

                    /**
                     * @param array<\App\Services\DataLoader\Schema\Company> $items
                     *
                     * @noinspection PhpMissingParentConstructorInspection
                     */
                    public function __construct(array $items) {
                        $this->items = $items;
                    }

                    public function getIterator(): Generator {
                        yield from $this->items;
                    }
                };
            });

        $service = Mockery::mock(DataLoaderService::class);
        $service
            ->shouldReceive('getClient')
            ->once()
            ->andReturn($client);

        // Bind
        $this->app->bind(Client::class, static function () use ($client) {
            return $client;
        });
        $this->app->bind(DataLoaderService::class, static function () use ($service) {
            return $service;
        });
        $this->app->bind(ResellerFactory::class, static function () use ($factory) {
            return $factory;
        });

        // Test
        $this->app->call([$this->app->make(ResellersImporterCronJob::class), 'handle']);

        Queue::assertPushed(ResellerUpdate::class, 2);
        Queue::assertPushed(ResellerUpdate::class, static function (ResellerUpdate $job) use ($b, $c): bool {
            return in_array($job->getResellerId(), [$b->id, $c->id], true);
        });
    }
}
