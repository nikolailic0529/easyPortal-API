<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Tag;
use App\Services\DataLoader\Cache\Retrievers\ClosureKey;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;

class TagResolver extends Resolver implements SingletonPersistent {
    public function get(string $name, Closure $factory = null): ?Tag {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($this->getUniqueKey($name), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Tag::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return Tag::query();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'unique' => new ClosureKey($this->normalizer, function (Tag $tag): array {
                return $this->getUniqueKey($tag->name);
            }),
        ];
    }

    /**
     * @return array{object_type: string, object_id: string, name: string|null, phone: string|null}
     */
    #[Pure]
    protected function getUniqueKey(string $name): array {
        return ['name' => $name];
    }
}
