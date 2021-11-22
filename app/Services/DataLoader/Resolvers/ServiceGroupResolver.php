<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver;
use App\Utils\Eloquent\Model;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;

class ServiceGroupResolver extends Resolver implements SingletonPersistent {
    public function get(Oem $oem, string $sku, Closure $factory = null): ?ServiceGroup {
        return $this->resolve($this->getUniqueKey($oem, $sku), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return ServiceGroup::query()->get();
    }

    public function getKey(Model $model): Key {
        return $model instanceof ServiceGroup
            ? $this->getCacheKey($this->getUniqueKey($model->oem_id, $model->sku))
            : parent::getKey($model);
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
