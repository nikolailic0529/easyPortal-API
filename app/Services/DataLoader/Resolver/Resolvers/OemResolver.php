<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\Oem;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @extends Resolver<Oem>
 */
class OemResolver extends Resolver implements SingletonPersistent {
    /**
     * @var array<string, Oem>
     */
    protected array $models = [];

    /**
     * @param Closure(): Oem|null $factory
     *
     * @return ($factory is null ? Oem|null : Oem)
     */
    public function get(string $key, Closure $factory = null): ?Oem {
        return $this->resolve($this->getUniqueKey($key), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Oem::query()->get();
    }

    public function getKey(Model $model): Key {
        return $this->getCacheKey($this->getUniqueKey($model->key));
    }

    /**
     * @return array{key: string}
     */
    protected function getUniqueKey(string $key): array {
        return [
            'key' => $key,
        ];
    }

    public function getByKey(?string $key): ?Oem {
        // Preload
        $this->getCache();

        // Return
        return $this->models[$key] ?? null;
    }

    protected function put(Model|array|Collection $object): void {
        if ($object instanceof Model) {
            $this->models[$object->getKey()] = $object;
        }

        parent::put($object);
    }

    public function reset(): static {
        $this->models = [];

        return parent::reset();
    }
}
