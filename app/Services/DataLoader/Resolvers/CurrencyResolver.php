<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Currency;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CurrencyResolver extends Resolver {
    public function get(string $code, Closure $factory = null): ?Currency {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($code, $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Currency::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return Currency::query();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'code' => new ClosureKey(static function (Currency $currency): string {
                return $currency->code;
            }),
        ];
    }
}
