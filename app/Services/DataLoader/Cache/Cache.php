<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

use function array_values;
use function spl_object_hash;

/**
 * @template TModel of Model
 */
class Cache {
    protected const NULL_RETRIEVER = self::class;

    /**
     * @var array<Collection<string, ?TModel>>
     */
    protected array $items;
    /**
     * @var array<KeyRetriever<TModel>>
     */
    protected array $retrievers;

    /**
     * @param array<KeyRetriever<TModel>> $retrievers
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

    /**
     * @return TModel|null
     */
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

    /**
     * @param TModel $model
     *
     * @return TModel
     */
    public function put(Model $model): Model {
        foreach ($this->retrievers as $name => $retriever) {
            $key = (string) $retriever->getKey($model);

            $this->items[static::NULL_RETRIEVER]->forget([$key]);
            $this->items[$name]->put($key, $model);
        }

        return $model;
    }

    /**
     * @param Collection<array-key, TModel> $models
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

    /**
     * @return TModel|null
     */
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
     * @return EloquentCollection<int, TModel>
     */
    public function getAll(): EloquentCollection {
        $all = [];

        foreach ($this->items as $key => $items) {
            if ($key === static::NULL_RETRIEVER) {
                continue;
            }

            foreach ($items as $item) {
                /** @var TModel $item */
                $all[$item->getKey() ?: spl_object_hash($item)] = $item;
            }
        }

        return EloquentCollection::make(array_values($all));
    }
}
