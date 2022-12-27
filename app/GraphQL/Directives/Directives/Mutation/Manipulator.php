<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation;

use LastDragon_ru\LaraASP\GraphQL\Utils\AstManipulator;
use Nuwave\Lighthouse\Support\Contracts\Directive;

class Manipulator extends AstManipulator {
    /**
     * @param class-string<Directive> $directive
     */
    public function getDirectiveName(string $directive): string {
        return $this->getDirectives()::directiveName($directive);
    }
}
