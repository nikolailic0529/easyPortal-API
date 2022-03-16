<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

use function array_values;
use function spl_object_hash;

class Cache {
    protected const NULL_RETRIEVER = self::class;

    /**
     * @var array<Collection<string, Model>>
     */
    protected array $items;
    /**
     * @var array<KeyRetriever>
     */
    protected array $retrievers;

    /**
     * @param array<KeyRetriever> $retrievers
     */
    public function __construct(array $retrievers) {
        $this->retrievers = $retrievers;

        $this->reset();
    }

    public function has(Key $key): bool {
        $value = $this->hasNull($key);

        if (!$value) {
            foreach ($this->retrievers as $name => $retriever) {
                if ($this->hasByRetriever($name, $key)) {
                    $value = true;
                    break;
                }
            }
        }

        return $value;
    }

    public function get(Key $key): ?Model {
        $value = null;

        if (!$this->hasNull($key)) {
            foreach ($this->retrievers as $name => $retriever) {
                if ($this->hasByRetriever($name, $key)) {
                    $value = $this->getByRetriever($name, $key);
                    break;
                }
            }
        }

        return $value;
    }

    public function put(Model $model): Model {
        foreach ($this->retrievers as $name => $retriever) {
            $key = (string) $retriever->getKey($model);

            $this->items[static::NULL_RETRIEVER]->forget([$key]);
            $this->items[$name]->put($key, $model);
        }

        return $model;
    }

    /**
     * @param Collection<array-key, Model> $models
     */
    public function putAll(Collection $models): static {
        foreach ($models as $model) {
            $this->put($model);
        }

        return $this;
    }

    public function putNull(Key $key): static {
        $this->items[static::NULL_RETRIEVER]->put((string) $key, null);

        return $this;
    }

    /**
     * @param array<Key> $keys
     */
    public function putNulls(array $keys): static {
        foreach ($keys as $key) {
            $this->putNull($key);
        }

        return $this;
    }

    public function hasNull(Key $key): bool {
        return $this->hasByRetriever(static::NULL_RETRIEVER, $key);
    }

    public function hasByRetriever(string $retriever, Key $key): bool {
        return isset($this->items[$retriever])
            && $this->items[$retriever]->has((string) $key);
    }

    public function getByRetriever(string $retriever, Key $key): ?Model {
        return isset($this->items[$retriever])
            ? $this->items[$retriever]->get((string) $key)
            : null;
    }

    public function reset(): static {
        $this->items = [
            static::NULL_RETRIEVER => new Collection(),
        ];

        foreach ($this->retrievers as $name => $retriever) {
            $this->items[$name] = new Collection();
        }

        return $this;
    }

    /**
     * @return Collection<string, Model>
     */
    public function getAll(): Collection {
        $all = [];

        foreach ($this->items as $key => $items) {
            if ($key === static::NULL_RETRIEVER) {
                continue;
            }

            foreach ($items as $item) {
                /** @var Model $item */
                $all[$item->getKey() ?: spl_object_hash($item)] = $item;
            }
        }

        return new Collection(array_values($all));
    }
}
