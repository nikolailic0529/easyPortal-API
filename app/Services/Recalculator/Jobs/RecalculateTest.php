<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Jobs;

use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\Model;
use App\Utils\Processor\Processor;
use Exception;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;
use Tests\TestCase;

use function array_map;
use function sort;

/**
 * @internal
 * @coversDefaultClass \App\Services\Recalculator\Jobs\Recalculate
 */
class RecalculateTest extends TestCase {
    /**
     * @covers ::init
     */
    public function testInitIds(): void {
        $keys = [$this->faker->uuid, $this->faker->uuid];
        $job  = new class() extends Recalculate {
            public function displayName(): string {
                return 'test';
            }

            protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
                throw new Exception('Should not be called.');
            }
        };

        $job->init($keys);

        sort($keys);

        $this->assertEquals($keys, $job->getKeys());
    }

    /**
     * @covers ::init
     */
    public function testInitModels(): void {
        $model  = new class() extends Model {
            // empty
        };
        $models = [
            (clone $model)->forceFill([
                $model->getKeyName() => $this->faker->uuid,
            ]),
            (clone $model)->forceFill([
                $model->getKeyName() => $this->faker->uuid,
            ]),
        ];
        $job    = new class() extends Recalculate {
            public function displayName(): string {
                return 'test';
            }

            protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
                throw new Exception('Should not be called.');
            }
        };

        $job->init($models);

        $keys = array_map(new GetKey(), $models);

        sort($keys);

        $this->assertEquals($keys, $job->getKeys());
    }
}
