<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use App\Utils\Iterators\Contracts\IteratorFatalError;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\Exceptions\BrokenIteratorDetected;
use App\Utils\Iterators\Exceptions\ClosureIteratorConvertError;
use Closure;
use Exception;
use Throwable;

use function count;

/**
 * @template TItem
 * @template TValue
 *
 * @extends ObjectIteratorIterator<TItem,TValue, ClosureIteratorConvertError>
 */
class ClosureIteratorIterator extends ObjectIteratorIterator {
    /**
     * @param ObjectIterator<TValue> $internalIterator
     * @param Closure(TValue): TItem $converter
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
     * @return Closure(TValue): TItem
     */
    protected function getConverter(): Closure {
        return $this->converter;
    }
    // </editor-fold>

    // <editor-fold desc="Abstract">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function chunkConvert(array $items): array {
        // Convert
        $converter = $this->getConverter();
        $converted = [];
        $errors    = 0;
        $valid     = 0;

        foreach ($items as $key => $item) {
            try {
                $item = $converter($item);

                if ($item !== null) {
                    $converted[$key] = $item;
                } else {
                    $this->error(
                        new ClosureIteratorConvertError($item),
                    );
                }

                $valid++;
            } catch (IteratorFatalError $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                if (!($exception instanceof Exception)) {
                    $errors++;
                }

                $this->error(
                    new ClosureIteratorConvertError($item, $exception),
                );
            }
        }

        // Broken?
        if (count($items) > 1 && $valid === 0 && $errors === count($items)) {
            throw new BrokenIteratorDetected($this::class);
        }

        // Return
        return $converted;
    }
    // </editor-fold>
}
