<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Concerns;

use App\Services\DataLoader\Client\Exceptions\GraphQLRequestFailed;
use App\Services\DataLoader\Exceptions\FailedToProcessItem;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Throwable;

/**
 * @template T
 * @template V
 */
trait SafeChunkItemConverter {
    abstract protected function getExceptionHandler(): ExceptionHandler;

    /**
     * @param T $item
     *
     * @return V
     */
    protected function chunkConvertItem(mixed $item): mixed {
        try {
            return parent::chunkConvertItem($item);
        } catch (GraphQLRequestFailed $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->getExceptionHandler()->report(new FailedToProcessItem($item, $exception));
        }

        return null;
    }
}
