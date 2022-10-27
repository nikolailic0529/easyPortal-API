<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;

use function array_filter;
use function array_merge;
use function array_unique;
use function array_values;

/**
 * @implements ArrayAccess<string, array<string>>
 * @implements Arrayable<string, array<string>>
 */
class Context implements ArrayAccess, Arrayable {
    public const DISTRIBUTORS = 'distributors';
    public const RESELLERS    = 'resellers';
    public const CUSTOMERS    = 'customers';
    public const ASSETS       = 'assets';
    public const TYPES        = 'types';
    public const FILES        = 'files';

    /**
     * @param array<string, array<string>> $context
     */
    public function __construct(
        private array $context = [],
    ) {
        // empty
    }

    // <editor-fold desc="Arrayable">
    // =========================================================================
    /**
     * @return array<string, non-empty-array<string>>
     */
    public function toArray(): array {
        return array_filter($this->context);
    }
    // </editor-fold>

    // <editor-fold desc="ArrayAccess">
    // =========================================================================
    public function offsetExists(mixed $offset): bool {
        return isset($this->context[$offset]);
    }

    /**
     * @return array<string>
     */
    public function offsetGet(mixed $offset): array {
        return (array) ($this->context[$offset] ?? null);
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->context[$offset] = array_values(array_unique(array_merge($this->context[$offset] ?? [], $value)));
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->context[$offset]);
    }
    //</editor-fold>
}
