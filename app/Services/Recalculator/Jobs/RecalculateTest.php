<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Jobs;

use App\Utils\Processor\Processor;
use Exception;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Recalculator\Jobs\Recalculate
 */
class RecalculateTest extends TestCase {
    /**
     * @covers ::init
     */
    public function testInit(): void {
        $key = $this->faker->uuid;
        $job = new class() extends Recalculate {
            public function displayName(): string {
                return 'test';
            }

            protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
                throw new Exception('Should not be called.');
            }
        };

        $job->init($key);

        self::assertEquals($key, $job->getModelKey());
    }
}
