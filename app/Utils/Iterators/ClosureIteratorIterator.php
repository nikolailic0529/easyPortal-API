<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use App\Utils\Iterators\Contracts\IteratorFatalError;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\Exceptions\BrokenIteratorDetected;
use Closure;
use Exception;
use Throwable;

use function count;

/**
 * @template TItem
 * @template TValue
 *
 * @extends ObjectIteratorIterator<TItem,TValue>
 */
class ClosureIteratorIterator extends ObjectIteratorIterator {
    /**
     * @param ObjectIterator<TValue>  $internalIterator
     * @param Closure(TValue): ?TItem $converter
     */
    public function __construct(
        ObjectIterator $internalIterator,
        protected Closure $converter,
    ) {
        parent::__construct($internalIterator);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    /**
     * @return Closure(TValue): ?TItem
     */
    protected function getConverter(): Closure {
        return $this->converter;
    }
    // </editor-fold>

    // <editor-fold desc="Convert">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function chunkConvert(array $items): array {
        // Convert
        $converter = $this->getConverter();
        $errors    = 0;
        $valid     = 0;
        $chunk     = [];

        foreach ($items as $key => $item) {
            try {
                $converted = $converter($item);

                if ($converted !== null) {
                    $chunk[$key] = $converted;
                } else {
                    $this->report($item, null);
                }

                $valid++;
            } catch (IteratorFatalError $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                if (!($exception instanceof Exception)) {
                    $errors++;
                }

                $this->report($item, $exception);
            }
        }

        // Broken?
        if (count($items) > 1 && $valid === 0 && $errors === count($items) && $errors === $this->getChunkSize()) {
            throw new BrokenIteratorDetected($this::class);
        }

        // Return
        return $chunk;
    }
    // </editor-fold>
}
