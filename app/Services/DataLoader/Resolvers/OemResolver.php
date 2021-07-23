<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Oem;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OemResolver extends Resolver {
    public function get(string $key, Closure $factory = null): ?Oem {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($key, $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Oem::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return Oem::query();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'key' => new ClosureKey(static function (Oem $oem): string {
                return $oem->key;
            }),
        ];
    }
}
