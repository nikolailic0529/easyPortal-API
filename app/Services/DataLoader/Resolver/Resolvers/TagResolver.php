<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\Tag;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @extends Resolver<Tag>
 */
class TagResolver extends Resolver implements SingletonPersistent {
    /**
     * @param Closure(?Tag): Tag|null $factory
     *
     * @return ($factory is null ? Tag|null : Tag)
     */
    public function get(string $name, Closure $factory = null): ?Tag {
        return $this->resolve($this->getUniqueKey($name), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Tag::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return Tag::query();
    }

    public function getKey(Model $model): Key {
        return $this->getCacheKey($this->getUniqueKey($model->name));
    }

    /**
     * @return array{name: string}
     */
    protected function getUniqueKey(string $name): array {
        return [
            'name' => $name,
        ];
    }
}
