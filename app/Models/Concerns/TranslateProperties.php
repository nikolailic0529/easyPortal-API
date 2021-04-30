<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use function __;
use function in_array;

/**
 * @see \App\GraphQL\Contracts\Translatable
 * @see \App\GraphQL\Directives\Directives\Translate
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
        $keys  = $this->getTranslatedPropertyKeys($property);
        $value = null;

        foreach ($keys as $key) {
            $translated = __($key);

            if ($translated !== $key) {
                $value = $translated;
                break;
            }
        }

        if (!$value) {
            $value = $this[$property];
        }

        // Return
        return $value;
    }

    /**
     * @return array<string>
     */
    abstract protected function getTranslatableProperties(): array;

    /**
     * @return array<string>
     */
    abstract protected function getTranslatedPropertyKeys(string $property): array;
}
