<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Normalizer\Normalizers\StringNormalizer;
use App\Utils\Cache\CacheKey;

use function is_string;
use function ksort;
use function mb_strtolower;

class Key extends CacheKey {
    /**
     * @inheritDoc
     */
    public function __construct(
        protected Normalizer $normalizer,
        array $key,
    ) {
        parent::__construct($key);
    }

    /**
     * @inheritDoc
     */
    protected function normalize(array $key): array {
        // Order of keys is not critical for objects search, but very helpful
        // for SQL queries consistency while testing.
        ksort($key);

        return parent::normalize($key);
    }

    protected function value(mixed $value): mixed {
        $value = parent::value($value);

        if (is_string($value)) {
            $value = StringNormalizer::normalize($value);
            $value = mb_strtolower($value);
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    protected function join(array $parts): string {
        return mb_strtolower(parent::join($parts));
    }

    protected function hash(string $value): string {
        return mb_strtolower(parent::hash($value));
    }

    protected function encode(mixed $value): string {
        return mb_strtolower(parent::encode($value));
    }
}
