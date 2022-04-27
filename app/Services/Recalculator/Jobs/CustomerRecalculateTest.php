<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Jobs;

use App\Services\Recalculator\Processor\Processors\CustomersProcessor;
use App\Utils\Processor\Processor;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Recalculator\Jobs\CustomerRecalculate
 */
class CustomerRecalculateTest extends TestCase {
    /**
     * @covers ::getProcessor
     * @covers ::makeProcessor
     */
    public function testGetProcessor(): void {
        $key = $this->faker->uuid();
        $job = new class() extends CustomerRecalculate {
            public function getProcessor(Container $container, QueueableConfig $config): Processor {
                return parent::getProcessor($container, $config);
            }
        };

        $job->init($key);

        $configurator = $this->app->make(QueueableConfigurator::class);
        $processor    = $job->getProcessor($this->app, $configurator->config($job));

        self::assertInstanceOf(CustomersProcessor::class, $processor);
        self::assertEquals([$key], $processor->getKeys());
    }
}
