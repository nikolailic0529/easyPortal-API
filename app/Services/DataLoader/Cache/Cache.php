<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use App\Models\Model;
use App\Services\DataLoader\Normalizers\KeyNormalizer;
use Illuminate\Support\Collection;

class Cache {
    protected KeyNormalizer $normalizer;
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
        $this->normalizer = new KeyNormalizer();
        $this->retrievers = $retrievers;
        $this->items      = [];

        foreach ($retrievers as $name => $retriever) {
            $this->items[$name] = $items->keyBy(function (Model $item) use ($retriever): string {
                return $this->normalizer->normalize($retriever->get($item));
            });
        }
    }

    public function has(mixed $key): bool {
        $value = false;

        foreach ($this->retrievers as $name => $retriever) {
            if ($this->hasByRetriever($name, $key)) {
                $value = true;
                break;
            }
        }

        return $value;
    }

    public function get(mixed $key): ?Model {
        $value = null;

        foreach ($this->retrievers as $name => $retriever) {
            if ($this->hasByRetriever($name, $key)) {
                $value = $this->getByRetriever($name, $key);
                break;
            }
        }

        return $value;
    }

    public function put(Model $model): Model {
        foreach ($this->retrievers as $name => $retriever) {
            $this->items[$name]->put(
                $this->normalizer->normalize($retriever->get($model)),
                $model,
            );
        }

        return $model;
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
