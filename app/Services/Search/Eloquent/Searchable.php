<?php declare(strict_types = 1);

namespace App\Services\Search\Eloquent;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Services\Search\Builder as SearchBuilder;
use App\Services\Search\ScopeWithMetadata;
use App\Utils\ModelProperty;
use Carbon\CarbonInterface;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Laravel\Scout\Searchable as ScoutSearchable;
use LogicException;

use function app;
use function array_intersect;
use function array_key_exists;
use function array_keys;
use function array_walk_recursive;
use function is_iterable;
use function is_null;
use function is_scalar;
use function sprintf;

/**
 * @mixin \App\Models\Model
 */
trait Searchable {
    use GlobalScopes;
    use ScoutSearchable {
        search as protected scoutSearch;
        makeAllSearchable as protected scoutMakeAllSearchable;
    }

    // <editor-fold desc="Abstract">
    // =========================================================================
    /**
     * Returns searchable properties that must be added to the index.
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
     *          'name' => 'name',               // $model->name
     *          'product' => [
     *              'sku'  => 'product.sku',    // $model->product->sku
     *              'name' => 'product.name',   // $model->product->name
     *          ],
     *      ]
     *
     * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
     */
    abstract protected static function getSearchProperties(): array;

    /**
     * Returns properties that must be added to the index as metadata.
     *
     * @see getSearchProperties()
     *
     * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
     */
    protected static function getSearchMetadata(): array {
        return [];
    }
    // </editor-fold>

    // <editor-fold desc="Scout">
    // =========================================================================
    public function searchIndexShouldBeUpdated(): bool {
        $properties = (new Collection($this->getSearchableProperties()))
            ->flatten()
            ->filter(static function (string $property): bool {
                return (new ModelProperty($property))->isAttribute();
            })
            ->all();
        $changed    = array_keys($this->getDirty());
        $should     = (bool) array_intersect($changed, $properties);

        return $should;
    }

    protected function makeAllSearchableUsing(Builder $query): Builder {
        return $query->with($this->getSearchableRelations());
    }

    /**
     * @return array<string,mixed>
     */
    public function toSearchableArray(): array {
        // Eager Loading
        $this->loadMissing($this->getSearchableRelations());

        // Get values
        $properties = $this->getSearchableProperties();

        array_walk_recursive($properties, function (mixed &$value): void {
            $value = $this->toSearchableValue((new ModelProperty($value))->getValue($this));
        });

        // Return
        return $properties;
    }

    public static function makeAllSearchable(int $chunk = null): void {
        static::callWithoutGlobalScope(OwnedByOrganizationScope::class, static function () use ($chunk): void {
            static::callWithoutScoutQueue(static function () use ($chunk): void {
                static::scoutMakeAllSearchable($chunk);
            });
        });
    }

    public static function search(string $query = '', Closure $callback = null): SearchBuilder {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return static::scoutSearch($query, $callback);
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @return array<string>
     */
    protected function getSearchableRelations(): array {
        return (new Collection($this->getSearchableProperties()))
            ->flatten()
            ->map(static function (string $property): ModelProperty {
                return new ModelProperty($property);
            })
            ->filter(static function (ModelProperty $property): bool {
                return $property->isRelation();
            })
            ->map(static function (ModelProperty $property): string {
                return $property->getRelationName();
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
     */
    protected function getSearchableProperties(): array {
        $properties = [
            SearchBuilder::METADATA   => $this->getSearchMetadata(),
            SearchBuilder::PROPERTIES => $this->getSearchProperties(),
        ];

        foreach ($this->getGlobalScopes() as $scope) {
            if ($scope instanceof ScopeWithMetadata) {
                foreach ($scope->getSearchMetadata($this) as $key => $metadata) {
                    // Metadata should be unique to avoid any possible side effects.
                    if (array_key_exists($key, $properties[SearchBuilder::METADATA])) {
                        throw new LogicException(sprintf(
                            'The `%s` trying to redefine `%s` in metadata.',
                            $scope::class,
                            $key,
                        ));
                    }

                    // Add
                    $properties[SearchBuilder::METADATA][$key] = $metadata;
                }
            }
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
