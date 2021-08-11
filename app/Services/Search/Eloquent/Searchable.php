<?php declare(strict_types = 1);

namespace App\Services\Search\Eloquent;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Configuration;
use App\Services\Search\Properties\Property;
use App\Utils\ModelProperty;
use Carbon\CarbonInterface;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Laravel\Scout\Events\ModelsImported;
use Laravel\Scout\Searchable as ScoutSearchable;
use Laravel\Telescope\Telescope;
use LogicException;

use function app;
use function array_filter;
use function array_intersect;
use function array_keys;
use function array_walk_recursive;
use function config;
use function count;
use function event;
use function is_iterable;
use function is_null;
use function is_scalar;

/**
 * @mixin \App\Models\Model
 */
trait Searchable {
    use GlobalScopes;
    use ScoutSearchable {
        search as protected scoutSearch;
        queueMakeSearchable as protected scoutQueueMakeSearchable;
    }

    // <editor-fold desc="Abstract">
    // =========================================================================
    /**
     * Returns properties that must be added to the index.
     *
     * *Warning:* If array structure is changed the search index MUST be rebuilt.
     *
     * Should return array where:
     * - `key`   - key that will be used in index;
     * - `value` - property name (not value!) that may contain dots to get
     *             properties from related models, OR array with properties;
     *
     * Example:
     *      [
     *          'name' => new Text('name', true),         // $model->name
     *          'product' => [
     *              'sku'  => new Text('product.sku'),    // $model->product->sku
     *              'name' => new Text('product.name'),   // $model->product->name
     *          ],
     *      ]
     *
     * @return array<string,\App\Services\Search\Properties\Property|array<string,\App\Services\Search\Properties\Property|array<string,\App\Services\Search\Properties\Property|array<string,\App\Services\Search\Properties\Property>>>>
     */
    abstract public static function getSearchProperties(): array;

    /**
     * Returns properties that must be added to the index as metadata.
     *
     * @see getSearchProperties()
     *
     * @return array<string,\App\Services\Search\Properties\Property|array<string,\App\Services\Search\Properties\Property|array<string,\App\Services\Search\Properties\Property|array<string,\App\Services\Search\Properties\Property>>>>
     */
    protected static function getSearchMetadata(): array {
        return [];
    }
    // </editor-fold>

    // <editor-fold desc="Scout">
    // =========================================================================
    public function shouldBeSearchable(): bool {
        return count(array_filter($this->getSearchableConfiguration()->getProperties())) > 0;
    }

    public function searchIndexShouldBeUpdated(): bool {
        $properties = (new Collection($this->getSearchableConfiguration()->getProperties()))
            ->flatten()
            ->map(static function (Property $property): ?string {
                return (new ModelProperty($property->getName()))->isAttribute()
                    ? $property->getName()
                    : null;
            })
            ->filter()
            ->all();
        $changed    = array_keys($this->getDirty());
        $should     = (bool) array_intersect($changed, $properties);

        return $should;
    }

    protected function makeAllSearchableUsing(Builder $query): Builder {
        return $query->with($this->getSearchableConfiguration()->getRelations());
    }

    /**
     * @return array<string,mixed>
     */
    public function toSearchableArray(): array {
        // Eager Loading & Values
        $configuration = $this->getSearchableConfiguration();
        $properties    = $configuration->getProperties();

        $this->loadMissing($configuration->getRelations());

        array_walk_recursive($properties, function (mixed &$value): void {
            $value = $value instanceof Property
                ? $this->toSearchableValue((new ModelProperty($value->getName()))->getValue($this))
                : $value;
        });

        // Return
        return $properties;
    }

    public static function makeAllSearchable(
        int $chunk = null,
        string $continue = null,
        Closure $callback = null,
    ): void {
        static::callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            static function () use ($chunk, $continue, $callback): void {
                static::callWithoutScoutQueue(static function () use ($chunk, $continue, $callback): void {
                    Telescope::withoutRecording(static function () use ($chunk, $continue, $callback): void {
                        $chunk  ??= config('scout.chunk.searchable', 500);
                        $trashed  = static::usesSoftDelete() && config('scout.soft_delete', false);
                        $callback = static function (EloquentCollection $items) use ($callback): void {
                            // Empty?
                            if ($items->isEmpty()) {
                                return;
                            }

                            // Event (needed for scout:import)
                            event(new ModelsImported($items));

                            // Callback
                            if ($callback) {
                                $callback($items);
                            }
                        };
                        $iterator = static::query()
                            ->when(true, static function (Builder $builder): void {
                                $builder->newModelInstance()->makeAllSearchableUsing($builder);
                            })
                            ->when($trashed, static function (Builder $builder): void {
                                $builder->withTrashed();
                            })
                            ->changeSafeIterator()
                            ->onAfterChunk($callback)
                            ->setChunkSize($chunk)
                            ->setOffset($continue);

                        foreach ($iterator as $model) {
                            $model->searchable();
                        }
                    });
                });
            },
        );
    }

    public function queueMakeSearchable(Collection $models): void {
        // shouldBeSearchable() is not used here by default...
        // https://github.com/laravel/scout/issues/320
        $this->scoutQueueMakeSearchable($models->filter->shouldBeSearchable());
    }

    public static function search(string $query = '', Closure $callback = null): SearchBuilder {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return static::scoutSearch($query, $callback);
    }
    // </editor-fold>

    // <editor-fold desc="Search">
    // =========================================================================
    public function getSearchableConfiguration(): Configuration {
        return app()->make(Configuration::class, [
            'model'      => $this,
            'metadata'   => $this->getSearchMetadata(),
            'properties' => $this->getSearchProperties(),
        ]);
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    protected function toSearchableValue(mixed $value): mixed {
        if ($value instanceof CarbonInterface) {
            $value = $value->toJSON();
        } elseif ($value instanceof DateTimeInterface) {
            $value = $this->toSearchableValue(Date::make($value));
        } elseif (is_iterable($value)) {
            $value = (new Collection($value))
                ->map(function (mixed $value): mixed {
                    return $this->toSearchableValue($value);
                })
                ->filter()
                ->unique()
                ->all();
        } elseif (is_scalar($value) || is_null($value)) {
            // no action
        } else {
            throw new LogicException('Not yet supported.');
        }

        return $value;
    }

    protected static function callWithoutScoutQueue(Closure $closure): mixed {
        $key      = 'scout.queue';
        $config   = app()->make(Repository::class);
        $previous = $config->get($key);

        try {
            $config->set($key, false);

            return $closure();
        } finally {
            $config->set($key, $previous);
        }
    }
    // </editor-fold>
}
