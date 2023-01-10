<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory;

use App\Services\DataLoader\Container\Isolated;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use Illuminate\Contracts\Debug\ExceptionHandler;

/**
 * Factories implement logic on how to create an application's model from an
 * external entity.
 *
 * Important notes:
 * - factories must not cache anything
 *
 * @template TModel of Model
 *
 * @internal
 */
abstract class Factory implements Isolated {
    public function __construct(
        protected ExceptionHandler $exceptionHandler,
    ) {
        // empty
    }

    protected function getExceptionHandler(): ExceptionHandler {
        return $this->exceptionHandler;
    }

    /**
     * @return TModel|null
     */
    abstract public function create(Type $type, bool $force = false): ?Model;

    /**
     * @return class-string<TModel>
     */
    abstract public function getModel(): string;
}
