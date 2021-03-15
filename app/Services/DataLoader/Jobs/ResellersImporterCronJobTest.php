<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Organization;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\DataLoaderService;
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
        Queue::fake();

        $o = Organization::factory()->create();
        $a = Company::create(['id' => $o->getKey()]);
        $b = Company::create(['id' => $this->faker->uuid]);
        $c = Company::create(['id' => $this->faker->uuid]);

        $client = Mockery::mock(Client::class);
        $client
            ->shouldReceive('getResellers')
            ->once()
            ->andReturnUsing(static function () use ($a, $b, $c): Generator {
                yield from [$a, $b, $c];
            });

        $service = Mockery::mock(DataLoaderService::class);
        $service
            ->shouldReceive('getClient')
            ->once()
            ->andReturn($client);

        $this->app
            ->make(ResellersImporterCronJob::class)
            ->handle($this->app, $service);

        Queue::assertPushed(ResellerUpdate::class, 2);
        Queue::assertPushed(ResellerUpdate::class, static function (ResellerUpdate $job) use ($b, $c): bool {
            return in_array($job->getResellerId(), [$b->id, $c->id], true);
        });
    }
}
