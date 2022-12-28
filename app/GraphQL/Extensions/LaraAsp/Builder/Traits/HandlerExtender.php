<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\Builder\Traits;

use App\GraphQL\Extensions\LaraAsp\Builder\Contracts\Extender;
use Illuminate\Support\Collection;
use LastDragon_ru\LaraASP\GraphQL\Builder\Directives\HandlerDirective;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;
use Nuwave\Lighthouse\Execution\Arguments\ArgumentSet;

/**
 * @mixin HandlerDirective
 */
trait HandlerExtender {
    protected function call(object $builder, Property $property, ArgumentSet $operator): object {
        $filter = static function (mixed $directive) use ($builder): bool {
            return $directive instanceof Extender
                && $directive->isBuilderSupported($builder);
        };

        foreach ($operator->arguments as $name => $argument) {
            /** @var Collection<int, Extender> $extenders */
            $extenders = $argument->directives->filter($filter);

            foreach ($extenders as $extender) {
                $builder = $extender->extend($builder, $property->getChild($name));
            }

            break;
        }

        return parent::call($builder, $property, $operator);
    }
}
