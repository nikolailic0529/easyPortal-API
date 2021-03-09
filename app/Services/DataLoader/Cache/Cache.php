<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use App\Models\Model;
use Illuminate\Support\Collection;

class Cache {
    protected const NULL_RETRIEVER = self::class;

    protected Normalizer $normalizer;
    /**
     * @var array<\Illuminate\Support\Collection<\App\Models\Model>>
     */
    protected array $items;
    /**
     * @var array<\App\Services\DataLoader\Cache\KeyRetriever>
     */
    protected array $retrievers;

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Model>  $items
     * @param array<\App\Services\DataLoader\Cache\KeyRetriever> $retrievers
     */
    public function __construct(Collection $items, array $retrievers) {
        $this->normalizer = new Normalizer();
        $this->retrievers = $retrievers;
        $this->items      = [
            static::NULL_RETRIEVER => new Collection(),
        ];

        foreach ($retrievers as $name => $retriever) {
            $this->items[$name] = $items->keyBy(function (Model $item) use ($retriever): string {
                return $this->normalizer->normalize($retriever->get($item));
            });
        }
    }

    public function has(mixed $key): bool {
        $value = $this->hasByRetriever(static::NULL_RETRIEVER, $key);

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

        if (!$this->hasByRetriever(static::NULL_RETRIEVER, $key)) {
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

    public function putNull(mixed $key): void {
        $this->items[static::NULL_RETRIEVER]->put(
            $this->normalizer->normalize($key),
            null,
        );
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
}
