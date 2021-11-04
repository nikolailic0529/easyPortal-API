<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Org;

use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\ModelProperty;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;
use InvalidArgumentException;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\ArgBuilderDirective;

use function sprintf;
use function str_ends_with;

// TODO Update property description?

abstract class Property extends BaseDirective implements ArgBuilderDirective {
    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Return property value for current organization.
            """
            directive @orgProperty on FIELD_DEFINITION | INPUT_FIELD_DEFINITION
        GRAPHQL;
    }

    /**
     * @template T of \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     *
     * @param T $builder
     *
     * @return T
     */
    public function handleBuilder($builder, mixed $value): EloquentBuilder|QueryBuilder {
        // Query?
        if (!($builder instanceof EloquentBuilder)) {
            throw new InvalidArgumentException(sprintf(
                'Builder must be instance of `%s`, `%s` given.',
                EloquentBuilder::class,
                $builder::class,
            ));
        }

        // Has scope?
        /** @var \Illuminate\Database\Eloquent\Model&\App\Services\Organization\Eloquent\OwnedByOrganization $model */
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
        $column   = $model->getOrganizationColumn();
        $property = new ModelProperty($column);
        $relation = $property->getRelation($builder);

        if (!($relation instanceof BelongsToMany)) {
            throw new InvalidArgumentException(sprintf(
                'Relation `%s` is not supported.',
                $relation::class,
            ));
        }

        // Root organization should always use original property
        if ($this->organization->isRoot()) {
            return $builder;
        }

        // Default select?
        if (!$builder->getQuery()->columns) {
            $builder = $builder->addSelect($builder->qualifyColumn('*'));
        }

        // Already added?
        $name   = $this->definitionNode->name->value;
        $added  = false;
        $marker = $builder->getGrammar()->wrap("_org_property__{$name}");

        foreach ($builder->getQuery()->columns as $column) {
            if ($column instanceof Expression && str_ends_with($column->getValue(), $marker)) {
                $added = true;
                break;
            }
        }

        if ($added) {
            return $builder;
        }

        // Add property
        $query   = $relation->getQuery()
            ->select($relation->qualifyPivotColumn($name))
            ->where($relation->qualifyColumn($property->getName()), '=', $this->organization->getKey())
            ->where(
                $relation->getQualifiedForeignPivotKeyName(),
                '=',
                new Expression(
                    $builder->getGrammar()->wrap($relation->getQualifiedParentKeyName()),
                ),
            )
            ->limit(1);
        $builder = $builder
            ->selectRaw("1 as {$marker}")
            ->selectSub($query, $name);

        // Return
        return $builder;
    }
}
