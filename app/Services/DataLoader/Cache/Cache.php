<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Cache {
    protected const NULL_RETRIEVER = self::class;

    protected Normalizer $normalizer;
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
        $this->normalizer = new Normalizer();
        $this->retrievers = $retrievers;

        $this->reset();
        $this->putAll($models);
    }

    public function has(mixed $key): bool {
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

    public function get(mixed $key): ?Model {
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
            $key = $this->normalizer->normalize($retriever->get($model));

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

    public function putNull(mixed $key): static {
        $this->items[static::NULL_RETRIEVER]->put(
            $this->normalizer->normalize($key),
            null,
        );

        return $this;
    }

    /**
     * @param array<mixed> $keys
     */
    public function putNulls(array $keys): static {
        foreach ($keys as $key) {
            $this->putNull($key);
        }

        return $this;
    }

    public function hasNull(mixed $key): bool {
        return $this->hasByRetriever(static::NULL_RETRIEVER, $key);
    }

    public function hasByRetriever(string $retriever, mixed $key): bool {
        $key = $this->normalizer->normalize($key);

        return isset($this->items[$retriever])
            && $this->items[$retriever]->has($key);
    }

    public function getByRetriever(string $retriever, mixed $key): ?Model {
        $key   = $this->normalizer->normalize($key);
        $value = isset($this->items[$retriever])
            ? $this->items[$retriever]->get($key)
            : null;

        return $value;
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
