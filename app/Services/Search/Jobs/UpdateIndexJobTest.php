<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs;

use App\Models\Asset;
use App\Services\Search\Updater;
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
        $updater = Mockery::mock(Updater::class);
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

        (new UpdateIndexJob(new Collection([$asset])))($this->app, $updater);

        Queue::assertNothingPushed();
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeIndexOutdated(): void {
        $updater = Mockery::mock(Updater::class);
        $updater
            ->shouldReceive('isIndexActual')
            ->once()
            ->andReturn(false);

        Queue::fake();

        (new UpdateIndexJob(new Collection([new Asset()])))($this->app, $updater);

        Queue::assertPushed(AssetsUpdaterCronJob::class);
    }
}
