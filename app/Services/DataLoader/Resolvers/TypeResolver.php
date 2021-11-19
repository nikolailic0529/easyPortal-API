<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Type;
use App\Services\DataLoader\Cache\Retrievers\ClosureKey;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver;
use App\Utils\Eloquent\Model;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;

class TypeResolver extends Resolver implements SingletonPersistent {
    public function get(Model $model, string $key, Closure $factory = null): ?Type {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($this->getUniqueKey($model, $key), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Type::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return Type::query();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'key' => new ClosureKey($this->normalizer, function (Type $type): array {
                return $this->getUniqueKey($type->object_type, $type->key);
            }),
        ];
    }

    /**
     * @return array{model: string, type: string}
     */
    #[Pure]
    protected function getUniqueKey(Model|string $model, string $key): array {
        return [
            'object_type' => $model instanceof Model ? $model->getMorphClass() : $model,
            'key'         => $key,
        ];
    }
}
