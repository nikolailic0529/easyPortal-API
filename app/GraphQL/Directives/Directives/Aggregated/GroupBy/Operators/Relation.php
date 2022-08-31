<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy\Operators;

use Illuminate\Support\Str;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Handler;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;
use Nuwave\Lighthouse\Execution\Arguments\Argument;
use Nuwave\Lighthouse\Execution\Arguments\ArgumentSet;

use function is_array;

class Relation extends AsString {
    public static function getName(): string {
        return 'relation';
    }

    public function getFieldDescription(): string {
        return 'Relationship clause.';
    }

    public function call(Handler $handler, object $builder, Property $property, Argument $argument): object {
        // Sort
        if (is_array($argument->value)) {
            foreach ($argument->value as $value) {
                if ($value instanceof ArgumentSet) {
                    $builder = $handler->handle($builder, $property, $value);
                }
            }
        }

        // Group
        $name    = Str::snake($property->getName());
        $base    = $property->getParent()->getChild("{$name}_id");
        $builder = parent::call($handler, $builder, $base, $argument);

        // Return
        return $builder;
    }

    protected function getKeyDirection(Argument $argument): ?string {
        return null;
    }
}
