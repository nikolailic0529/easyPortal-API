<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Oem;
use App\Models\OemGroup;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use JetBrains\PhpStorm\Pure;

class OemGroupResolver extends Resolver {
    public function get(Oem $model, string $key, string $name, Closure $factory = null): ?OemGroup {
        return $this->resolve($this->getUniqueKey($model, $key, $name), $factory);
    }

    protected function getFindQuery(): ?Builder {
        return OemGroup::query();
    }

    public function getKey(Model $model): Key {
        return $model instanceof OemGroup
            ? $this->getCacheKey($this->getUniqueKey($model->oem_id, $model->key, $model->name))
            : parent::getKey($model);
    }

    /**
     * @return array{oem_id: string, key: string, name: string}
     */
    #[Pure]
    protected function getUniqueKey(Oem|string $model, string $key, string $name): array {
        return [
            'oem_id' => $model instanceof Oem ? $model->getKey() : $model,
            'key'    => $key,
            'name'   => $name,
        ];
    }
}
