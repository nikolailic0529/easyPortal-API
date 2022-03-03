<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs;

use App\Services\Queue\Concerns\ProcessorJob;
use App\Services\Queue\Contracts\Progressable;
use App\Services\Queue\Job;
use App\Services\Search\Processor\Processor;
use App\Utils\Eloquent\Callbacks\GetKey;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;
use LastDragon_ru\LaraASP\Queue\Contracts\Initializable;

/**
 * Adds models into Search Index.
 *
 * @see \Laravel\Scout\Jobs\MakeSearchable
 * @see \Laravel\Scout\Jobs\RemoveFromSearch
 */
class Index extends Job implements Initializable, Progressable {
    use ProcessorJob;

    /**
     * @var class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable>|null
     */
    private ?string $model;

    /**
     * @var array<string|int>
     */
    private array $keys;

    /**
     * @param \Illuminate\Support\Collection<
     *     \Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable
     *     > $models
     */
    public function __construct(?Collection $models = null) {
        parent::__construct();

        if ($models !== null) {
            $model = $models->first();

            $this->init(
                $model ? $model::class : null,
                $models->map(new GetKey())->all(),
            );
        }
    }

    public function displayName(): string {
        return 'ep-search-index';
    }

    /**
     * @return class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable>|null
     */
    public function getModel(): ?string {
        return $this->model;
    }

    /**
     * @return array<string|int>
     */
    public function getKeys(): array {
        return $this->keys;
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable>|null $model
     * @param array<string|int>                                                                               $keys
     *
     * @return $this
     */
    public function init(?string $model, array $keys): static {
        $this->model = $model;
        $this->keys  = $keys;

        $this->initialized();

        return $this;
    }

    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        return $container
            ->make(Processor::class)
            ->setModel($this->getModel())
            ->setRebuild(false);
    }
}
