<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\Status;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @extends Resolver<Status>
 */
class StatusResolver extends Resolver implements SingletonPersistent {
    /**
     * @param Closure(?Status): Status|null $factory
     *
     * @return ($factory is null ? Status|null : Status)
     */
    public function get(Model $model, string $key, Closure $factory = null): ?Status {
        return $this->resolve($this->getUniqueKey($model, $key), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Status::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return Status::query();
    }

    public function getKey(Model $model): Key {
        return $this->getCacheKey($this->getUniqueKey($model->object_type, $model->key));
    }

    /**
     * @return array{object_type: string, key: string}
     */
    protected function getUniqueKey(Model|string $model, string $key): array {
        return [
            'object_type' => $model instanceof Model ? $model->getMorphClass() : $model,
            'key'         => $key,
        ];
    }
}
