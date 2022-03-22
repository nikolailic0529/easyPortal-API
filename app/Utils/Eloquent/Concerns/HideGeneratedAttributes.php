<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Concerns;

use App\Utils\Eloquent\Model;

use function str_starts_with;

/**
 * @mixin Model
 */
trait HideGeneratedAttributes {
    /**
     * @inheritDoc
     */
    public function setRawAttributes(array $attributes, $sync = false): static {
        return parent::setRawAttributes(static::removeGeneratedAttributes($attributes), $sync);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    public static function removeGeneratedAttributes(array $attributes): array {
        foreach ($attributes as $name => $value) {
            if ($name === 'deleted_not' || str_starts_with($name, '_')) {
                unset($attributes[$name]);
            }
        }

        return $attributes;
    }
}
