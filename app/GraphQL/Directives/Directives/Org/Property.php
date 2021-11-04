<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Org;

use App\Services\Organization\CurrentOrganization;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use InvalidArgumentException;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\ArgBuilderDirective;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;

use function sprintf;

// TODO Update property description?

abstract class Property extends BaseDirective implements ArgBuilderDirective, FieldResolver {
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

    public function resolveField(FieldValue $fieldValue): FieldValue {
        // Root organization should always use original property
        if ($this->organization->isRoot()) {
            return $fieldValue;
        }

        // Add resolver
        return $fieldValue;
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

        // Add property
        $name  = $this->definitionNode->name->value;
        $query = (new Loader($this->organization, $name))->getQuery($builder);

        if ($query) {
            $builder = $builder->selectSub($query->limit(1), $name);
        }

        // Return
        return $builder;
    }
}
