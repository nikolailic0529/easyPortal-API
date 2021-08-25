<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs;

use App\Models\Callbacks\GetKey;
use App\Services\Queue\Job;
use App\Services\Search\Service;
use App\Services\Search\Updater;
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
    protected ?string $model;

    /**
     * @var array<string|int>
     */
    protected array $ids;

    /**
     * @param \Illuminate\Support\Collection<
     *     \Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable
     *     > $models
     *
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(Collection $models) {
        $this->model = $models->first() ? $models->first()::class : null;
        $this->ids   = $models->map(new GetKey())->all();
    }

    public function displayName(): string {
        return 'ep-search-updater';
    }

    public function __invoke(Container $container, Updater $updater): void {
        // Is there something that should be updated?
        if (!$this->model) {
            return;
        }

        // First, we should check the status of the index - maybe it needs to be
        // rebuilt, in this case, we must run CronJob (that will rebuild and
        // update it).
        if (!$updater->isIndexActual($this->model)) {
            $container->make(Service::getSearchableModelJob($this->model))->dispatch();

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
