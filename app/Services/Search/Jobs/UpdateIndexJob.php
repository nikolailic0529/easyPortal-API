<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs;

use App\Services\Queue\Job;
use App\Services\Search\Processor\Processor;
use App\Services\Search\Service;
use App\Utils\Eloquent\Callbacks\GetKey;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;

/**
 * Adds models into Search Index.
 *
 * @see \Laravel\Scout\Jobs\MakeSearchable
 * @see \Laravel\Scout\Jobs\RemoveFromSearch
 */
class UpdateIndexJob extends Job {
    /**
     * @var class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable>|null
     */
    private ?string $model;

    /**
     * @var array<string|int>
     */
    private array $ids;

    /**
     * @param \Illuminate\Support\Collection<
     *     \Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable
     *     > $models
     *
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(?Collection $models = null) {
        $models ??= new Collection();

        $this->setModels(
            $models->first() ? $models->first()::class : null,
            $models->map(new GetKey())->all(),
        );
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
    public function getIds(): array {
        return $this->ids;
    }

    public function displayName(): string {
        return 'ep-search-updater';
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable>|null $model
     * @param array<string|int>                                                                               $ids
     *
     * @return $this
     */
    public function setModels(?string $model, array $ids): static {
        $this->model = $model;
        $this->ids   = $ids;

        return $this;
    }

    public function __invoke(Container $container, Service $service, Processor $updater): void {
        // Is there something that should be updated?
        if (!$this->model) {
            return;
        }

        // First, we should check the status of the index - maybe it needs to be
        // rebuilt, in this case, we must run CronJob (that will rebuild and
        // update it).
        if (!$updater->isIndexActual($this->model)) {
            $container->make($service->getSearchableModelJob($this->model))->dispatch();

            return;
        }

        // Index is actual => update
        $updater
            ->onChange(function (): void {
                $this->ping();
            })
            ->update($this->model, ids: $this->ids);
    }
}
