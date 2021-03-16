<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Organization;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Client\QueryIterator;
use App\Services\DataLoader\DataLoaderService;
use App\Services\DataLoader\Schema\Company;
use Generator;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Psr\Log\LoggerInterface;
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
        Queue::fake();

        $o      = Organization::factory()->create();
        $a      = Company::create(['id' => $o->getKey()]);
        $b      = Company::create(['id' => $this->faker->uuid]);
        $c      = Company::create(['id' => $this->faker->uuid]);
        $items  = [$a, $b, $c];
        $logger = $this->app->make(LoggerInterface::class);

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

        $this->app
            ->make(ResellersImporterCronJob::class)
            ->handle($this->app, $logger, $service);

        Queue::assertPushed(ResellerUpdate::class, 2);
        Queue::assertPushed(ResellerUpdate::class, static function (ResellerUpdate $job) use ($b, $c): bool {
            return in_array($job->getResellerId(), [$b->id, $c->id], true);
        });
    }
}
