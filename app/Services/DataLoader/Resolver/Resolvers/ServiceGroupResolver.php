<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\Oem;
use App\Models\Data\ServiceGroup;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @extends Resolver<ServiceGroup>
 */
class ServiceGroupResolver extends Resolver implements SingletonPersistent {
    /**
     * @param Closure(?ServiceGroup): ServiceGroup|null $factory
     *
     * @return ($factory is null ? ServiceGroup|null : ServiceGroup)
     */
    public function get(Oem $oem, string $sku, Closure $factory = null): ?ServiceGroup {
        return $this->resolve($this->getUniqueKey($oem, $sku), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return ServiceGroup::query()->get();
    }

    public function getKey(Model $model): Key {
        return $this->getCacheKey($this->getUniqueKey($model->oem_id, $model->sku));
    }

    /**
     * @return array{oem_id: string, sku: string}
     */
    protected function getUniqueKey(Oem|string $oem, string $sku): array {
        return [
            'oem_id' => $oem instanceof Model ? $oem->getKey() : $oem,
            'sku'    => $sku,
        ];
    }
}
