<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;

use function assert;

abstract class Count extends BaseDirective implements FieldResolver {
    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Returns `count(*)`.
            """
            directive @aggregatedCount on FIELD_DEFINITION
            GRAPHQL;
    }

    public function resolveField(FieldValue $fieldValue): FieldValue {
        return $fieldValue->setResolver(
            static function (mixed $root): int {
                assert($root instanceof BuilderValue);

                $builder = $root->getBuilder();
                $count   = 0;

                if ($builder instanceof EloquentBuilder) {
                    $base    = $builder->toBase();
                    $builder = $base->distinct === true
                        ? $base->distinct($builder->getModel()->getQualifiedKeyName())
                        : $base;
                }

                if ($builder instanceof QueryBuilder && !!$builder->groups) {
                    $count = $builder->getCountForPagination();
                } else {
                    $count = $builder->count();
                }

                return $count;
            },
        );
    }
}
