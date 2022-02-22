<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Jobs;

use App\Services\Recalculator\Processor\Processors\LocationsProcessor;
use App\Utils\Processor\Processor;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Job as QueueJob;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use Mockery;
use Tests\TestCase;

use function sort;

/**
 * @internal
 * @coversDefaultClass \App\Services\Recalculator\Jobs\LocationsRecalculate
 */
class LocationsRecalculateTest extends TestCase {
    /**
     * @covers ::getProcessor
     * @covers ::makeProcessor
     */
    public function testGetProcessor(): void {
        $keys     = [$this->faker->uuid, $this->faker->uuid];
        $job      = new class() extends LocationsRecalculate {
            public function getProcessor(Container $container, QueueableConfig $config): Processor {
                return parent::getProcessor($container, $config);
            }
        };
        $queueJob = Mockery::mock(QueueJob::class);
        $queueJob
            ->shouldReceive('getJobId')
            ->once()
            ->andReturn($this->faker->uuid);

        $job->setJob($queueJob)->init($keys);

        $configurator = $this->app->make(QueueableConfigurator::class);
        $processor    = $job->getProcessor($this->app, $configurator->config($job));

        sort($keys);

        $this->assertInstanceOf(LocationsProcessor::class, $processor);
        $this->assertEquals($keys, $processor->getKeys());
    }
}