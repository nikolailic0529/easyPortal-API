<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Tag;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;

class TagResolver extends Resolver {
    public function get(string $name, Closure $factory = null): ?Tag {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($this->getUniqueKey($name), $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Tag::query()->get();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'unique' => new ClosureKey(function (Tag $tag): array {
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
