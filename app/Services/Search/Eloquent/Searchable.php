<?php declare(strict_types = 1);

namespace App\Services\Search\Eloquent;

use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Search\Scout\OwnedByOrganizationScope;
use App\Utils\ModelProperty;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Laravel\Scout\Searchable as ScoutSearchable;
use LogicException;

use function array_intersect;
use function array_keys;
use function array_unique;
use function class_uses_recursive;
use function count;
use function in_array;
use function is_iterable;

/**
 * @mixin \App\Models\Model
 */
trait Searchable {
    use ScoutSearchable;

    // <editor-fold desc="Abstract">
    // =========================================================================
    /**
     * Returns properties and their values that must be added to the index.
     *
     * @return array<string>
     */
    abstract protected function getSearchableProperties(): array;

    /**
     * Returns relations that used in {@link \App\Services\Search\Eloquent\Searchable::getSearchableProperties()}
     *
     * @return array<string>
     */
    abstract protected function getSearchableRelations(): array;
    // </editor-fold>

    // <editor-fold desc="Scout">
    // =========================================================================
    public function shouldBeSearchable(): bool {
        return count($this->toSearchableArray()) > 0;
    }

    public function searchIndexShouldBeUpdated(): bool {
        $dirty      = array_keys($this->getDirty());
        $properties = array_keys($this->getSearchableProperties());

        if ($this->isOwnedByOrganization($this)) {
            /** @var \Illuminate\Database\Eloquent\Model&\App\Services\Organization\Eloquent\OwnedByOrganization $this */
            $property = new ModelProperty($this->getOrganizationColumn());

            if ($property->isAttribute()) {
                $properties[] = $property->getName();
            }
        }

        return (bool) array_intersect($dirty, $properties);
    }

    protected function makeAllSearchableUsing(Builder $query): Builder {
        $relations = $this->getSearchableRelations();

        if ($this->isOwnedByOrganization($this)) {
            /** @var \Illuminate\Database\Eloquent\Model&\App\Services\Organization\Eloquent\OwnedByOrganization $this */
            $property = new ModelProperty($this->getOrganizationColumn());

            if ($property->isRelation()) {
                $relations[] = $property->getRelationName();
            }
        }

        return $query->with(array_unique($relations));
    }

    /**
     * @return array<string,mixed>
     */
    public function toSearchableArray(): array {
        // Get properties.
        $properties = $this->getSearchableProperties();

        // Empty?
        if (!$properties) {
            return [];
        }

        // Organization?
        if ($this->isOwnedByOrganization($this)) {
            /** @var \Illuminate\Database\Eloquent\Model&\App\Services\Organization\Eloquent\OwnedByOrganization $this */
            $property                                  = new ModelProperty($this->getOrganizationColumn());
            $properties[OwnedByOrganizationScope::KEY] = $property->getValue($this);
        }

        // Prepare
        $searchable = $this->toSearchableValue($properties);

        // Return
        return $searchable;
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    protected static function isSearchable(Model $model): bool {
        return in_array(ScoutSearchable::class, class_uses_recursive($model), true);
    }

    protected static function isOwnedByOrganization(Model $model): bool {
        return in_array(OwnedByOrganization::class, class_uses_recursive($model), true);
    }

    protected function toSearchableValue(mixed $value): mixed {
        if ($value instanceof Model) {
            /** @var \Illuminate\Database\Eloquent\Model&\Laravel\Scout\Searchable $value */
            if ($this->isSearchable($value)) {
                $value = $value->toSearchableArray();
            } else {
                throw new LogicException('Not yet supported.');
            }
        } elseif ($value instanceof DateTimeInterface) {
            $value = $this->toSearchableValue(Date::make($value));
        } elseif ($value instanceof CarbonInterface) {
            $value = $value->toJSON();
        } elseif (is_iterable($value)) {
            $value = (new Collection($value))
                ->map(function (mixed $value): mixed {
                    return $this->toSearchableValue($value);
                })
                ->filter()
                ->unique()
                ->all();
        } else {
            // empty
        }

        return $value;
    }
    // </editor-fold>
}
