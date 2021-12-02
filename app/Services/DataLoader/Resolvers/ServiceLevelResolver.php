<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;

class ServiceLevelResolver extends Resolver implements SingletonPersistent {
    public function get(Oem $oem, ServiceGroup $group, string $sku, Closure $factory = null): ?ServiceLevel {
        return $this->resolve($this->getUniqueKey($oem, $group, $sku), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return ServiceLevel::query()->get();
    }

    public function getKey(Model $model): Key {
        return $model instanceof ServiceLevel
            ? $this->getCacheKey($this->getUniqueKey($model->oem_id, $model->service_group_id, $model->sku))
            : parent::getKey($model);
    }

    /**
     * @return array{oem_id: string, service_group_id: string, sku: string}
     */
    #[Pure]
    protected function getUniqueKey(Oem|string $oem, ServiceGroup|string $group, string $sku): array {
        return [
            'oem_id'           => $oem instanceof Model ? $oem->getKey() : $oem,
            'service_group_id' => $group instanceof Model ? $group->getKey() : $group,
            'sku'              => $sku,
        ];
    }
}
