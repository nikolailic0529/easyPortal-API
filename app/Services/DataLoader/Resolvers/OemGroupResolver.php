<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Oem;
use App\Models\OemGroup;
use App\Services\DataLoader\Cache\Retrievers\ClosureKey;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use JetBrains\PhpStorm\Pure;

class OemGroupResolver extends Resolver {
    public function get(Oem $model, string $key, string $name, Closure $factory = null): ?OemGroup {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($this->getUniqueKey($model, $key, $name), $factory);
    }

    protected function getFindQuery(): ?Builder {
        return OemGroup::query();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'key' => new ClosureKey($this->normalizer, function (OemGroup $group): array {
                return $this->getUniqueKey($group->oem_id, $group->key, $group->name);
            }),
        ];
    }

    /**
     * @return array{oem_id: string, key: string}
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
