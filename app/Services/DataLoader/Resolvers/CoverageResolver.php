<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Coverage;
use App\Services\DataLoader\Cache\Retrievers\ClosureKey;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CoverageResolver extends Resolver implements SingletonPersistent {
    public function get(string $key, Closure $factory = null): ?Coverage {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($key, $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Coverage::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return Coverage::query();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'key' => new ClosureKey($this->normalizer, static function (Coverage $coverage): array {
                return [$coverage->key];
            }),
        ];
    }
}
