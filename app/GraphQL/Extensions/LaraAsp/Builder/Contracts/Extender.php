<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\Builder\Contracts;

use Closure;
use GraphQL\Language\AST\DirectiveNode;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Handler;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\OperatorUnsupportedBuilder;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;

interface Extender {
    public function getFieldDirective(): ?DirectiveNode;

    public function isBuilderSupported(object $builder): bool;

    /**
     * @template TBuilder of object
     *
     * @param TBuilder                        $builder
     * @param Closure(object, Property): void $callback
     *
     * @throws OperatorUnsupportedBuilder if `$builder` is not supported
     *
     * @return TBuilder
     */
    public function extend(Handler $handler, object $builder, Property $property, Closure $callback): object;
}
