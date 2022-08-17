<?php declare(strict_types = 1);

namespace App\Services\I18n\Eloquent;

use App\Utils\Eloquent\Model;

use function in_array;
use function reset;
use function trans;

/**
 * @see \App\Services\I18n\Contracts\Translatable
 * @see \App\GraphQL\Directives\Directives\Translate
 *
 * @mixin Model
 */
trait TranslateProperties {
    public function getTranslatedProperty(string $property): string {
        // Can be translated?
        if (!in_array($property, $this->getTranslatableProperties(), true)) {
            return $this->getAttribute($property);
        }

        // Translate
        $keys  = $this->getTranslatedPropertyKeys($property);
        $value = null;

        foreach ($keys as $key) {
            $translated = trans($key);

            if ($translated !== $key) {
                $value = $translated;
                break;
            }
        }

        if (!$value) {
            $value = $this->getAttribute($property);
        }

        // Return
        return $value;
    }

    /**
     * @return array<string,string>
     */
    public function getDefaultTranslations(): array {
        $properties   = $this->getTranslatableProperties();
        $translations = [];

        foreach ($properties as $property) {
            $keys               = $this->getTranslatedPropertyKeys($property);
            $key                = reset($keys);
            $translations[$key] = $this->getAttribute($property);
        }

        return $translations;
    }

    /**
     * @return array<string>
     */
    abstract protected function getTranslatableProperties(): array;

    /**
     * @return array<string>
     */
    protected function getTranslatedPropertyKeys(string $property): array {
        $strings = [];
        $model   = $this->getMorphClass();
        $keys    = [
            $this->getTranslatableKey(),
            $this->getKey(),
        ];

        foreach ($keys as $key) {
            if ($key !== null) {
                $strings[] = "models.{$model}.{$key}.{$property}";
            }
        }

        return $strings;
    }

    protected function getTranslatableKey(): ?string {
        return null;
    }
}
