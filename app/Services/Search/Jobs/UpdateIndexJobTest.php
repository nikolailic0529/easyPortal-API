<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs;

use App\Models\Asset;
use App\Services\Search\Processor\Processor;
use App\Services\Search\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Jobs\UpdateIndexJob
 */
class UpdateIndexJobTest extends TestCase {
    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void {
        $asset   = (new Asset())->forceFill(['id' => $this->faker->uuid]);
        $service = $this->app->make(Service::class);
        $updater = Mockery::mock(Processor::class);
        $updater
            ->shouldReceive('isIndexActual')
            ->once()
            ->andReturn(true);
        $updater
            ->shouldReceive('onChange')
            ->once()
            ->andReturnSelf();
        $updater
            ->shouldReceive('update')
            ->with($asset::class, null, null, null, [$asset->getKey()])
            ->once()
            ->andReturns();

        Queue::fake();

        (new UpdateIndexJob(new Collection([$asset])))($this->app, $service, $updater);

        Queue::assertNothingPushed();
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeIndexOutdated(): void {
        $service = $this->app->make(Service::class);
        $updater = Mockery::mock(Processor::class);
        $updater
            ->shouldReceive('isIndexActual')
            ->once()
            ->andReturn(false);

        Queue::fake();

        (new UpdateIndexJob(new Collection([new Asset()])))($this->app, $service, $updater);

        Queue::assertPushed(AssetsUpdaterCronJob::class);
    }
}
