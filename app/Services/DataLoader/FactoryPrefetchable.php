<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use Closure;

interface FactoryPrefetchable {
    /**
     * @param array<\App\Services\DataLoader\Schema\Type> $objects
     * @param \Closure(\Illuminate\Database\Eloquent\Collection):void|null $callback
     */
    public function prefetch(array $objects, bool $reset = false, Closure|null $callback = null): static;
}
