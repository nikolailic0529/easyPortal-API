<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use App\Services\Search\Builders\Builder as SearchBuilder;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use InvalidArgumentException;
use Laravel\Scout\Builder as ScoutBuilder;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Scout\ScoutBuilderDirective;
use Nuwave\Lighthouse\Support\Contracts\ArgBuilderDirective;
use Nuwave\Lighthouse\Support\Contracts\FieldManipulator;

use function sprintf;

class Paginated extends BaseDirective implements FieldManipulator, ArgBuilderDirective, ScoutBuilderDirective {

    public function __construct(
        protected Container $container,
    ) {
        // empty
    }

    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Adds offset-based pagination for the field.
            """
            directive @paginated on FIELD_DEFINITION
            GRAPHQL;
    }

    public function manipulateFieldDefinition(
        DocumentAST &$documentAST,
        FieldDefinitionNode &$fieldDefinition,
        ObjectTypeDefinitionNode &$parentType,
    ): void {
        $this->container
            ->make(Manipulator::class, ['document' => $documentAST])
            ->update($fieldDefinition, $parentType);
    }

    /**
     * @inheritdoc
     */
    public function handleBuilder($builder, mixed $value): EloquentBuilder|QueryBuilder {
        return $this->handle($builder, $value);
    }

    public function handleScoutBuilder(ScoutBuilder $builder, mixed $value): ScoutBuilder {
        if (!($builder instanceof SearchBuilder)) {
            throw new InvalidArgumentException(sprintf(
                'The `$builder` must be instance of `%s`, `%s` given.',
                SearchBuilder::class,
                $builder::class,
            ));
        }

        return $this->handle($builder, $value);
    }

    /**
     * @template T of \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\App\Services\Search\Builders\Builder
     *
     * @param T $builder
     *
     * @return T
     */
    protected function handle(
        EloquentBuilder|QueryBuilder|SearchBuilder $builder,
        mixed $value,
    ): EloquentBuilder|QueryBuilder|SearchBuilder {
        if (isset($value['limit'])) {
            $builder = $builder->limit($value['limit']);

            if (isset($value['offset'])) {
                $builder = $builder->offset($value['offset']);
            }
        }

        return $builder;
    }
}
