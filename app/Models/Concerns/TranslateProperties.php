<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use function __;
use function in_array;

/**
 * @see \App\GraphQL\Contracts\Translatable
 * @see \App\GraphQL\Directives\TranslateDirective
 *
 * @mixin \App\Models\Model
 */
trait TranslateProperties {
    public function getTranslatedProperty(string $property): string {
        // Can be translated?
        if (!in_array($property, $this->getTranslatableProperties(), true)) {
            return $this[$property];
        }

        // Translate
        $key        = $this->getTranslatedPropertyKey($property);
        $translated = __($key);

        if ($translated === $key) {
            $translated = $this[$property];
        }

        // Return
        return $translated;
    }

    /**
     * @return array<string>
     */
    abstract protected function getTranslatableProperties(): array;

    abstract protected function getTranslatedPropertyKey(string $property): string;
}
