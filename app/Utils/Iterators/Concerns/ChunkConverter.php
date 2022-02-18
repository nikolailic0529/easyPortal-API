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
        $converted = [];
        $errors    = 0;

        foreach ($items as $key => $item) {
            try {
                $item = $this->chunkConvertItem($item);

                if ($item !== null) {
                    $converted[$key] = $item;
                }
            } catch (IteratorFatalError $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                if (!($exception instanceof Exception)) {
                    $errors++;
                }

                $this->report($exception, $item);
            }
        }

        // Broken?
        if (count($items) > 1 && count($converted) === 0 && $errors === count($items)) {
            throw new BrokenIteratorDetected($this::class);
        }

        // Return
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
     * @param V $item
     */
    protected function report(Throwable $exception, mixed $item): void {
        $this->getExceptionHandler()->report($exception);
    }

    /**
     * @return \Closure(V $item): T|null
     */
    abstract protected function getConverter(): ?Closure;

    abstract protected function getExceptionHandler(): ExceptionHandler;
}
