<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Type;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;

class TypeResolver extends Resolver implements SingletonPersistent {
    public function get(Model $model, string $key, Closure $factory = null): ?Type {
        return $this->resolve($this->getUniqueKey($model, $key), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Type::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return Type::query();
    }

    public function getKey(Model $model): Key {
        return $model instanceof Type
            ? $this->getCacheKey($this->getUniqueKey($model->object_type, $model->key))
            : parent::getKey($model);
    }

    /**
     * @return array{object_type: string, key: string}
     */
    #[Pure]
    protected function getUniqueKey(Model|string $model, string $key): array {
        return [
            'object_type' => $model instanceof Model ? $model->getMorphClass() : $model,
            'key'         => $key,
        ];
    }
}
