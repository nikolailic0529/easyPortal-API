<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Jobs;

use App\Services\Queue\Concerns\ProcessorJob;
use App\Services\Queue\Contracts\Progressable;
use App\Services\Queue\Job;
use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Processor\Processor;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;
use LastDragon_ru\LaraASP\Queue\Contracts\Initializable;

use function is_string;

/**
 * Recalculates properties of given models.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @uses     \App\Services\Queue\Concerns\ProcessorJob<\App\Utils\Processor\EloquentProcessor>
 */
abstract class Recalculate extends Job implements Initializable, Progressable {
    use ProcessorJob {
        getProcessor as private createProcessor;
    }

    /**
     * @var array<string>
     */
    protected array $keys;

    /**
     * @return array<string>
     */
    public function getKeys(): array {
        return $this->keys;
    }

    /**
     * @param array<string>
     *     |array<TModel>
     *     |\Illuminate\Support\Collection<string>
     *     |\Illuminate\Support\Collection<TModel> $models
     *
     * @return $this<TModel>
     */
    public function init(array|Collection $models): static {
        // Empty?
        $models = new Collection($models);

        if ($models->isEmpty()) {
            throw new InvalidArgumentException('The `$keys` cannot be empty.');
        }

        // Extract
        if (!is_string($models->first())) {
            $models = $models->map(new GetKey());
        }

        // Initialize
        $this->keys = $models->unique()->sort()->values()->all();

        $this->initialized();

        // Return
        return $this;
    }

    protected function getProcessor(Container $container, QueueableConfig $config): Processor {
        return $this
            ->createProcessor($container, $config)
            ->setKeys($this->getKeys());
    }
}
