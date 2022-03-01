<?php declare(strict_types = 1);

namespace App\Services\I18n\Contracts;

/**
 * @see \App\GraphQL\Directives\Definitions\TranslateDirective
 * @see \App\Services\I18n\Eloquent\TranslateProperties
 */
interface Translatable {
    public function getTranslatedProperty(string $property): string;
}
