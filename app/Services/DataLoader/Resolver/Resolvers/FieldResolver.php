<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Field;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @extends Resolver<Field>
 */
class FieldResolver extends Resolver implements SingletonPersistent {
    /**
     * @param Closure(Normalizer=): Field|null $factory
     *
     * @return ($factory is null ? Field|null : Field)
     */
    public function get(Model $model, string $key, Closure $factory = null): ?Field {
        return $this->resolve($this->getUniqueKey($model, $key), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Field::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return Field::query();
    }

    public function getKey(Model $model): Key {
        return $model instanceof Field
            ? $this->getCacheKey($this->getUniqueKey($model->object_type, $model->key))
            : parent::getKey($model);
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
