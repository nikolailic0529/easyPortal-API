<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\Currency;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @extends Resolver<Currency>
 */
class CurrencyResolver extends Resolver implements SingletonPersistent {
    /**
     * @param Closure(): Currency|null $factory
     *
     * @return ($factory is null ? Currency|null : Currency)
     */
    public function get(string $code, Closure $factory = null): ?Currency {
        return $this->resolve($this->getUniqueKey($code), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Currency::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return Currency::query();
    }

    public function getKey(Model $model): Key {
        return$this->getCacheKey($this->getUniqueKey($model->code));
    }

    /**
     * @return array{code: string}
     */
    protected function getUniqueKey(string $code): array {
        return [
            'code' => $code,
        ];
    }
}
