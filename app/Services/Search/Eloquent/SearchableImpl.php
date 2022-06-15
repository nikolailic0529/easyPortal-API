<?php declare(strict_types = 1);

namespace App\Services\Search\Eloquent;

use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Configuration;
use App\Services\Search\Indexer;
use App\Services\Search\Processors\ModelProcessor;
use App\Services\Search\Properties\Property;
use App\Services\Search\Properties\Relation;
use App\Services\Search\Properties\Value;
use App\Utils\Eloquent\ModelProperty;
use Carbon\CarbonInterface;
use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Laravel\Scout\Engines\Engine;
use Laravel\Scout\ModelObserver;
use Laravel\Scout\Searchable as ScoutSearchable;
use LogicException;

use function app;
use function array_filter;
use function array_intersect;
use function array_keys;
use function config;
use function count;
use function is_array;
use function is_iterable;
use function is_null;
use function is_scalar;

/**
 * @mixin \App\Utils\Eloquent\Model
 *
 * @method Engine searchableUsing()
 */
trait SearchableImpl {
    use ScoutSearchable {
        search as protected scoutSearch;
        searchable as protected scoutSearchable;
        unsearchable as protected scoutUnsearchable;
        searchableAs as protected scoutSearchableAs;
        enableSearchSyncing as protected scoutEnableSearchSyncing;
        disableSearchSyncing as protected scoutDisableSearchSyncing;
    }

    private ?string $searchableAs = null;

    // <editor-fold desc="Abstract">
    // =========================================================================
    /**
     * @return array<string,Property>
     */
    public static function getSearchMetadata(): array {
        return [];
    }
    // </editor-fold>

    // <editor-fold desc="Scout">
    // =========================================================================
    public function searchable(): void {
        $this->scoutSearchable();
    }

    public function unsearchable(): void {
        $this->scoutUnsearchable();
    }

    public function searchableAs(): string {
        return $this->searchableAs ?? $this->scoutSearchableAs();
    }

    public function shouldBeSearchable(): bool {
        return count(array_filter($this->getSearchConfiguration()->getProperties())) > 0;
    }

    public function searchIndexShouldBeUpdated(): bool {
        // Relations don't matter here because method used only in ModelObserver
        $properties = (new Collection($this->getSearchConfiguration()->getProperties()))
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

    /**
     * @return array<string,mixed>
     */
    public function toSearchableArray(): array {
        // Eager Loading
        $configuration = $this->getSearchConfiguration();

        $this->loadMissing($configuration->getRelations());

        // Values
        $array = [];

        foreach ($configuration->getProperties() as $key => $properties) {
            $array[$key] = $this->toSearchableArrayProcess($this, $properties);
        }

        // Remove empty nodes
        $array = $this->toSearchableArrayCleanup($array);

        // Return
        return $array;
    }

    public static function makeAllSearchable(int $chunk = null): void {
        app(ModelProcessor::class)
            ->setModel(static::class)
            ->setRebuild(true)
            ->setChunkSize($chunk)
            ->start();
    }

    /**
     * @param Builder<static> $query
     *
     * @return Builder<static>
     */
    public function makeAllSearchableUsing(Builder $query): Builder {
        return $query->with($this->getSearchConfiguration()->getRelations());
    }

    /**
     * @param EloquentCollection<array-key, static> $models
     */
    public function queueMakeSearchable(EloquentCollection $models): void {
        if (config('scout.queue')) {
            app()->make(Indexer::class)->update($models);
        } else {
            $this->searchableUsing()->update($models);
        }
    }

    /**
     * @param EloquentCollection<array-key, static> $models
     */
    public function queueRemoveFromSearch(EloquentCollection $models): void {
        if (config('scout.queue')) {
            app()->make(Indexer::class)->update($models);
        } else {
            $this->searchableUsing()->delete($models);
        }
    }

    /**
     * @return SearchBuilder<static>
     */
    public static function search(string $query = '', Closure $callback = null): SearchBuilder {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return static::scoutSearch($query, $callback);
    }
    // </editor-fold>

    // <editor-fold desc="Search">
    // =========================================================================
    public function getSearchConfiguration(): Configuration {
        return app()->make(Configuration::class, [
            'model'      => $this,
            'metadata'   => static::getSearchMetadata(),
            'properties' => static::getSearchProperties(),
        ]);
    }

    public function setSearchableAs(?string $searchableAs): static {
        $this->searchableAs = $searchableAs;

        return $this;
    }

    public function getSearchableAsDefault(): string {
        return $this->scoutSearchableAs();
    }

    public static function isSearchSyncingEnabled(): bool {
        return !ModelObserver::syncingDisabledFor(static::class);
    }

    public static function enableSearchSyncing(): void {
        static::scoutEnableSearchSyncing();
    }

    public static function disableSearchSyncing(): void {
        static::scoutDisableSearchSyncing();
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @param array<string, Property> $properties
     *
     * @return array<string, mixed>
     */
    protected function toSearchableArrayProcess(Model $model, array $properties): array {
        $values = [];

        foreach ($properties as $name => $property) {
            $value = (new ModelProperty($property->getName()))->getValue($model);

            if ($property instanceof Relation) {
                if ($value instanceof Collection) {
                    $values[$name] = $value
                        ->map(function (mixed $model) use ($property): mixed {
                            return $model instanceof Model
                                ? $this->toSearchableArrayProcess($model, $property->getProperties())
                                : null;
                        })
                        ->all();
                } elseif ($value instanceof Model) {
                    $values[$name] = $this->toSearchableArrayProcess($value, $property->getProperties());
                } else {
                    $values[$name] = null;
                }
            } elseif ($property instanceof Value) {
                $values[$name] = $this->toSearchableValue($value);
            } else {
                throw new LogicException('Not yet supported.');
            }
        }

        return $values;
    }

    /**
     * @template T
     *
     * @param array<string, T> $properties
     *
     * @return array<string, T|null>
     */
    protected function toSearchableArrayCleanup(array $properties): array {
        foreach ($properties as $property => $value) {
            // Node?
            if (!is_array($value)) {
                continue;
            }

            // Nested
            $value = $this->toSearchableArrayCleanup($value);

            // Empty? Remove
            $nulls                 = array_filter($value, static function (mixed $value): bool {
                return $value === null;
            });
            $properties[$property] = count($nulls) !== count($value)
                ? $value
                : null;
        }

        return $properties;
    }

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
    // </editor-fold>

    // <editor-fold desc="\ElasticScoutDriverPlus\Searchable">
    // =========================================================================
    /**
     * @see \ElasticScoutDriverPlus\Searchable::shardRouting()
     */
    public function shardRouting(): string|int|null {
        return null;
    }

    /**
     * @see \ElasticScoutDriverPlus\Searchable::searchableWith()
     *
     * @return array<string>|string|null
     */
    public function searchableWith(): array|string|null {
        return null;
    }
    // </editor-fold>
}
