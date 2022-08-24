<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators;

use App\GraphQL\Directives\Directives\Aggregated\GroupBy\Types\Direction;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Handler;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeProvider;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\OperatorUnsupportedBuilder;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;
use Nuwave\Lighthouse\Execution\Arguments\Argument;

class PropertyOperator extends BaseOperator {
    public static function getName(): string {
        return 'property';
    }

    public function getFieldType(TypeProvider $provider, string $type): ?string {
        return $provider->getType(Direction::class);
    }

    public function getFieldDescription(): string {
        return 'Property clause.';
    }

    public function call(Handler $handler, object $builder, Property $property, Argument $argument): object {
        // Supported?
        if (!($builder instanceof EloquentBuilder)) {
            throw new OperatorUnsupportedBuilder($this, $builder);
        }

        // Process
        // $direction = Cast::toString($argument->value);
        // todo(graphql)!: not implemented

        // Return
        return $builder;
    }
}
