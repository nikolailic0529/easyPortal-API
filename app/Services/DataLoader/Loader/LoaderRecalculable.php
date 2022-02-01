<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader;

/**
 * @see \App\Services\DataLoader\Loader\Concerns\WithCalculatedProperties
 */
interface LoaderRecalculable {
    public function setRecalculate(bool $recalculate): static;

    public function recalculate(bool $force = false): void;
}
