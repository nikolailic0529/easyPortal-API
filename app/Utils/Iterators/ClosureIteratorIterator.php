<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use App\Utils\Iterators\Concerns\ErrorableSubjects;
use App\Utils\Iterators\Contracts\Errorable;
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
 * @extends ObjectIteratorIterator<TItem|null,TValue>
 */
class ClosureIteratorIterator extends ObjectIteratorIterator implements Errorable {
    /**
     * @use ErrorableSubjects<TValue>
     */
    use ErrorableSubjects {
        __clone as __cloneErrorableSubjects;
    }

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
            $converted[$key] = null;

            try {
                $item            = $converter($item);
                $converted[$key] = $item;

                $valid++;
            } catch (IteratorFatalError $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                if (!($exception instanceof Exception)) {
                    $errors++;
                }

                $this->error($item, $exception);
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

    // <editor-fold desc="Functions">
    // =========================================================================
    /**
     * @param TValue $item
     */
    protected function error(mixed $item, Throwable $error): void {
        $this->getOnErrorDispatcher()->notify(
            new ClosureIteratorConvertError($item, $error),
        );
    }

    public function __clone(): void {
        parent::__clone();
        $this->__cloneErrorableSubjects();
    }
    // </editor-fold>
}
