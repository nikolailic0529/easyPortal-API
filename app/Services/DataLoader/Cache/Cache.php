<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Cache {
    protected const NULL_RETRIEVER = self::class;

    /**
     * @var array<\Illuminate\Support\Collection<\Illuminate\Database\Eloquent\Model>>
     */
    protected array $items;
    /**
     * @var array<\App\Services\DataLoader\Cache\KeyRetriever>
     */
    protected array $retrievers;

    /**
     * @param \Illuminate\Support\Collection<\Illuminate\Database\Eloquent\Model> $models
     * @param array<\App\Services\DataLoader\Cache\KeyRetriever>                  $retrievers
     */
    public function __construct(Collection $models, array $retrievers) {
        $this->retrievers = $retrievers;

        $this->reset();
        $this->putAll($models);
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
            $key = (string) $retriever->get($model);

            $this->items[static::NULL_RETRIEVER]->forget([$key]);
            $this->items[$name]->put($key, $model);
        }

        return $model;
    }

    /**
     * @param \Illuminate\Support\Collection<\Illuminate\Database\Eloquent\Model> $models
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
     * @param array<\App\Services\DataLoader\Cache\Key> $keys
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

    public function getAll(): Collection {
        $all = new Collection();

        foreach ($this->items as $items) {
            $all = $all->merge($items);
        }

        return $all->uniqueStrict()->filter()->values();
    }
}
