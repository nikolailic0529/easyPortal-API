<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache\Retrievers;

use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Cache\KeyRetriever;
use App\Services\DataLoader\Normalizer;
use App\Utils\Eloquent\Model;
use Closure;

class ClosureKey implements KeyRetriever {
    /**
     * @param \Closure(\App\Models\Model): array<mixed> $closure
     */
    public function __construct(
        protected Normalizer $normalizer,
        protected Closure $closure,
    ) {
        // empty
    }

    public function get(Model $model): Key {
        return new Key($this->normalizer, ($this->closure)($model));
    }
}
