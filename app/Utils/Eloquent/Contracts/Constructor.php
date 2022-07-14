<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Contracts;

/**
 * @see https://phpstan.org/blog/solving-phpstan-error-unsafe-usage-of-new-static
 */
interface Constructor {
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = []);
}
