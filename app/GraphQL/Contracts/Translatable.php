<?php declare(strict_types = 1);

namespace App\GraphQL\Contracts;

/**
 * @see \App\GraphQL\Directives\Definitions\TranslateDirective
 */
interface Translatable {
    public function getTranslatedProperty(string $property): string;
}
