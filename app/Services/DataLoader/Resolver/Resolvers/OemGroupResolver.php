<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\Oem;
use App\Models\OemGroup;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Resolver<OemGroup>
 */
class OemGroupResolver extends Resolver {
    /**
     * @param Closure(?OemGroup): OemGroup|null $factory
     *
     * @return ($factory is null ? OemGroup|null : OemGroup)
     */
    public function get(Oem $model, string $key, string $name, Closure $factory = null): ?OemGroup {
        return $this->resolve($this->getUniqueKey($model, $key, $name), $factory);
    }

    protected function getFindQuery(): ?Builder {
        return OemGroup::query();
    }

    public function getKey(Model $model): Key {
        return $this->getCacheKey($this->getUniqueKey($model->oem_id, $model->key, $model->name));
    }

    /**
     * @return array{oem_id: string, key: string, name: string}
     */
    protected function getUniqueKey(Oem|string $model, string $key, string $name): array {
        return [
            'oem_id' => $model instanceof Oem ? $model->getKey() : $model,
            'key'    => $key,
            'name'   => $name,
        ];
    }
}
