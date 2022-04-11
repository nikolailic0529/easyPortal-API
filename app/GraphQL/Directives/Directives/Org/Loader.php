<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Org;

use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\ModelProperty;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Nuwave\Lighthouse\Execution\ModelsLoader\ModelsLoader;
use stdClass;

use function sprintf;
use function str_ends_with;

class Loader implements ModelsLoader {
    public function __construct(
        protected CurrentOrganization $organization,
        protected string $property,
        protected mixed $default = null,
    ) {
        // empty
    }

    protected function getProperty(): string {
        return $this->property;
    }

    protected function getOwner(): string {
        return 'id';
    }

    protected function getMarker(): string {
        return "org_property__{$this->getProperty()}";
    }

    /**
     * @param EloquentCollection<int, Model> $parents
     */
    public function load(EloquentCollection $parents): void {
        // Root organization should always use original property
        if ($this->organization->isRoot()) {
            return;
        }

        // Remove loaded
        $marker  = $this->getMarker();
        $parents = $parents->filter(static function (Model $parent) use ($marker): bool {
            return !isset($parent[$marker]);
        });

        if ($parents->isEmpty()) {
            return;
        }

        // Load
        $builder  = $parents->first()::query();
        $owner    = $this->getOwner();
        $values   = $this->getQuery($builder, $parents)->toBase()->get()->keyBy($owner);
        $property = $this->getProperty();

        foreach ($parents as $parent) {
            /** @var Model $parent */
            $value = $values->get($parent->getKey());
            $value = $value instanceof stdClass
                ? ($value->{$property} ?? null)
                : null;

            $parent->setAttribute($property, $value);
        }
    }

    public function extract(Model $model): mixed {
        return $model->getAttribute($this->getProperty()) ?? $this->default;
    }

    public function getQuery(Builder $builder, Collection $parents = null): ?Builder {
        // Has scope?
        /** @var Model&OwnedByOrganization $model */
        $model = $builder->getModel();
        $scope = OwnedByOrganizationScope::class;

        if (!$model::hasGlobalScope($scope)) {
            throw new InvalidArgumentException(sprintf(
                'Model `%s` doesn\'t use the `%s` scope.',
                $model::class,
                $scope,
            ));
        }

        // Relation?
        $property = new ModelProperty($model->getOrganizationColumn());
        $relation = $property->getRelation($builder);

        if (!($relation instanceof BelongsToMany)) {
            throw new InvalidArgumentException(sprintf(
                'Relation `%s` is not supported.',
                $relation::class,
            ));
        }

        // Root organization should always use original property
        if ($this->organization->isRoot()) {
            return null;
        }

        // Default select?
        if (!$builder->getQuery()->columns) {
            $builder = $builder->addSelect($builder->qualifyColumn('*'));
        }

        // Already added?
        $added  = false;
        $marker = $builder->getGrammar()->wrap($this->getMarker());

        foreach ($builder->getQuery()->columns as $column) {
            if ($column instanceof Expression && str_ends_with($column->getValue(), $marker)) {
                $added = true;
                break;
            }
        }

        if ($added) {
            return null;
        }

        // Add marker
        $builder = $builder->selectRaw("1 as {$marker}");

        // Add property
        $key       = $relation->getQualifiedForeignPivotKeyName();
        $owner     = $this->getOwner();
        $parentKey = new Expression(
            $builder->getGrammar()->wrap($relation->getQualifiedParentKeyName()),
        );

        return $relation
            ->getQuery()
            ->select($relation->qualifyPivotColumn($this->getProperty()))
            ->where($relation->qualifyColumn($property->getName()), '=', $this->organization->getKey())
            ->when(
                $parents,
                static function (Builder $builder) use ($owner, $key, $parents): void {
                    $builder->addSelect("{$key} as {$owner}");
                    $builder->whereIn($key, $parents->map(new GetKey())->all());
                },
                static function (Builder $builder) use ($key, $parentKey): void {
                    $builder->where($key, '=', $parentKey);
                },
            );
    }
}
