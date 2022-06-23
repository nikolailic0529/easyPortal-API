<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Queue\Tasks;

use App\Services\Queue\Concerns\ProcessorJob;
use App\Services\Queue\Job;
use App\Services\Recalculator\Processor\ChunkData;
use App\Services\Recalculator\Processor\Processor;
use App\Services\Recalculator\Service;
use App\Utils\Processor\EloquentState;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;
use LastDragon_ru\LaraASP\Queue\Contracts\Initializable;

use function assert;
use function is_a;

abstract class Recalculate extends Job implements Initializable {
    /**
     * @use ProcessorJob<Processor<Model, ChunkData<Model>, EloquentState<Model>>>
     */
    use ProcessorJob;

    /**
     * @return Processor<Model, ChunkData<Model>, EloquentState<Model>>
     */
    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        $service   = $container->make(Service::class);
        $processor = $service->getRecalculableModelProcessor($this->getModel());

        assert($processor && is_a($processor, Processor::class, true));

        return $container->make($processor)
            ->setKeys($this->getKeys());
    }

    /**
     * @return class-string<Model>
     */
    abstract protected function getModel(): string;

    /**
     * @return array<string|int>
     */
    abstract protected function getKeys(): array;
}
