<?php declare(strict_types = 1);

namespace App\Utils\JsonObject;

use Attribute;

/**
 * Used to specify how the value should be normalized before assignment.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class JsonObjectNormalizer {
    /**
     * @param class-string<Normalizer> $normalizer
     */
    public function __construct(
        protected string $normalizer,
    ) {
        // empty
    }

    public function normalize(mixed $value): mixed {
        return $this->normalizer::normalize($value);
    }
}
