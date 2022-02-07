<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Concerns;

use Closure;

/**
 * @template T
 * @template V
 */
trait ChunkConverter {
    /**
     * @param array<V> $items
     *
     * @return array<T>
     */
    protected function chunkConvert(array $items): array {
        $converted = [];

        foreach ($items as $key => $item) {
            $item = $this->chunkConvertItem($item);

            if ($item !== null) {
                $converted[$key] = $item;
            }
        }

        return $converted;
    }

    /**
     * @param T $item
     *
     * @return V
     */
    protected function chunkConvertItem(mixed $item): mixed {
        $converter = $this->getConverter();
        $converted = $item;

        if ($converter) {
            $converted = $converter($item);
        }

        return $converted;
    }

    /**
     * @return \Closure(V $item): T|null
     */
    abstract protected function getConverter(): ?Closure;
}
