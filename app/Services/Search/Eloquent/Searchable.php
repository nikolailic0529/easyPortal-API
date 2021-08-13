<?php declare(strict_types = 1);

namespace App\Services\Search\Eloquent;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Configuration;
use App\Services\Search\Properties\Property;
use App\Services\Search\Updater;
use App\Utils\ModelProperty;
use Carbon\CarbonInterface;
use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Laravel\Scout\Searchable as ScoutSearchable;
use Laravel\Scout\SearchableScope;
use LogicException;

use function app;
use function array_filter;
use function array_intersect;
use function array_keys;
use function array_walk_recursive;
use function count;
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
        searchableAs as public scoutSearchableAs;
        queueMakeSearchable as protected scoutQueueMakeSearchable;
    }

    private ?string $searchableAs = null;

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
    abstract protected static function getSearchProperties(): array;

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
    /**
     * FIXME: Temporary fix for DataLoader.
     */
    public static function bootSearchable(): void {
        static::addGlobalScope(new SearchableScope());

        (new static())->registerSearchableMacros();
    }

    public function searchableAs(): string {
        return $this->searchableAs ?? $this->scoutSearchableAs();
    }

    public function shouldBeSearchable(): bool {
        return count(array_filter($this->getSearchConfiguration()->getProperties())) > 0;
    }

    public function searchIndexShouldBeUpdated(): bool {
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
        // Eager Loading & Values
        $configuration = $this->getSearchConfiguration();
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

    public static function makeAllSearchable(int $chunk = null): void {
        app(Updater::class)->update(static::class, chunk: $chunk);
    }

    public function makeAllSearchableUsing(Builder $query): Builder {
        return $query->with($this->getSearchConfiguration()->getRelations());
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
    public function getSearchConfiguration(): Configuration {
        return app()->make(Configuration::class, [
            'model'      => $this,
            'metadata'   => $this->getSearchMetadata(),
            'properties' => $this->getSearchProperties(),
        ]);
    }

    public function setSearchableAs(?string $searchableAs): static {
        $this->searchableAs = $searchableAs;

        return $this;
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
    // </editor-fold>
}
