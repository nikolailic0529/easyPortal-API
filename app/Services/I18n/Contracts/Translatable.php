<?php declare(strict_types = 1);

namespace App\Services\I18n\Contracts;

/**
 * @see \App\GraphQL\Directives\Definitions\TranslateDirective
 * @see \App\Services\I18n\Eloquent\TranslateProperties
 */
interface Translatable {
    /**
     * Should return translated property value.
     */
    public function getTranslatedProperty(string $property): string;

    /**
     * Should return default translations for all translatable properties.
     *
     * @return array<string,string>
     */
    public function getDefaultTranslations(): array;
}
