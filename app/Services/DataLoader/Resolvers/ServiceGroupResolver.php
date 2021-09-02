<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Model;
use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;

class ServiceGroupResolver extends Resolver implements SingletonPersistent {
    public function get(Oem $oem, string $sku, Closure $factory = null): ?ServiceGroup {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($this->getUniqueKey($oem, $sku), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return ServiceGroup::query()->get();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'unique' => new ClosureKey(function (ServiceGroup $group): array {
                return $this->getUniqueKey($group->oem_id, $group->sku);
            }),
        ];
    }

    /**
     * @return array{oem_id: string, sku: string}
     */
    #[Pure]
    protected function getUniqueKey(Oem|string $oem, string $sku): array {
        return [
            'oem_id' => $oem instanceof Model ? $oem->getKey() : $oem,
            'sku'    => $sku,
        ];
    }
}
