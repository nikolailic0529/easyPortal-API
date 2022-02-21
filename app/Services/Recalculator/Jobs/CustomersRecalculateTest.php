<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Jobs;

use App\Services\Recalculator\Processor\Processors\CustomersProcessor;
use App\Utils\Processor\Processor;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use Tests\TestCase;

use function sort;

/**
 * @internal
 * @coversDefaultClass \App\Services\Recalculator\Jobs\CustomersRecalculate
 */
class CustomersRecalculateTest extends TestCase {
    /**
     * @covers ::getProcessor
     * @covers ::makeProcessor
     */
    public function testGetProcessor(): void {
        $keys = [$this->faker->uuid, $this->faker->uuid];
        $job  = new class() extends CustomersRecalculate {
            public function getProcessor(Container $container, QueueableConfig $config): Processor {
                return parent::getProcessor($container, $config);
            }
        };

        $job->init($keys);

        $configurator = $this->app->make(QueueableConfigurator::class);
        $processor    = $job->getProcessor($this->app, $configurator->config($job));

        sort($keys);

        $this->assertInstanceOf(CustomersProcessor::class, $processor);
        $this->assertEquals($keys, $processor->getKeys());
    }
}
