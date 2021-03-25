<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Document;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class DocumentResolver extends Resolver {
    public function get(string|int $id, Closure $factory = null): ?Document {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($id, $factory);
    }

    /**
     * @param array<string|int> $keys
     */
    public function prefetch(array $keys, bool $reset = false, Closure|null $callback = null): static {
        return parent::prefetch($keys, $reset, $callback);
    }

    protected function getFindQuery(): ?Builder {
        return Document::query();
    }
}
