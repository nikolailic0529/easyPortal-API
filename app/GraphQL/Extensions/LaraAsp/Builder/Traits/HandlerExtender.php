<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\Builder\Traits;

use App\GraphQL\Extensions\LaraAsp\Builder\Contracts\Extender;
use LastDragon_ru\LaraASP\GraphQL\Builder\Directives\HandlerDirective;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;
use Nuwave\Lighthouse\Execution\Arguments\ArgumentSet;

use function array_key_first;
use function reset;

/**
 * @mixin HandlerDirective
 */
trait HandlerExtender {
    protected function call(object $builder, Property $property, ArgumentSet $operator): object {
        $extended = $builder;
        $argument = reset($operator->arguments);

        if ($argument) {
            $name     = array_key_first($operator->arguments);
            $filter   = static function (mixed $directive) use ($builder): bool {
                return $directive instanceof Extender
                    && $directive->isBuilderSupported($builder);
            };
            $extender = $argument->directives->filter($filter)->first();

            if ($name && $extender instanceof Extender) {
                $extender->extend(
                    $this,
                    $builder,
                    new Property($name),
                    function (object $builder, Property $property) use (&$extended, $argument): void {
                        $extended = null;
                        $operator = $argument->value;

                        if ($operator instanceof ArgumentSet) {
                            parent::call($builder, $property, $operator);
                        }
                    },
                );
            }
        }

        if ($extended) {
            $builder = parent::call($extended, $property, $operator);
        }

        return $builder;
    }
}
