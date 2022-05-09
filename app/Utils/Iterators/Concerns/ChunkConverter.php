<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Concerns;

use App\Utils\Iterators\Contracts\IteratorFatalError;
use App\Utils\Iterators\Exceptions\BrokenIteratorDetected;
use Closure;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Throwable;

use function count;

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
        // Convert
        $converter = $this->getConverter();
        $converted = [];
        $errors    = 0;

        if ($converter) {
            foreach ($items as $key => $item) {
                try {
                    $item            = $converter($item);
                    $converted[$key] = $item;
                } catch (IteratorFatalError $exception) {
                    throw $exception;
                } catch (Throwable $exception) {
                    if (!($exception instanceof Exception)) {
                        $errors++;
                    }

                    $this->report($exception, $item);
                }
            }
        } else {
            /** @var array<T> $converted */
            $converted = $items;
        }

        // Broken?
        if (count($items) > 1 && count($converted) === 0 && $errors === count($items)) {
            throw new BrokenIteratorDetected($this::class);
        }

        // Return
        return $converted;
    }

    /**
     * @param V $item
     */
    protected function report(Throwable $exception, mixed $item): void {
        $this->getExceptionHandler()->report($exception);
    }

    /**
     * @return Closure(V): T|null
     */
    abstract protected function getConverter(): ?Closure;

    abstract protected function getExceptionHandler(): ExceptionHandler;
}
