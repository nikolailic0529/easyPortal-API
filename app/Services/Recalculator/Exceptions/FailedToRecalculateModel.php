<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Exceptions;

use App\Exceptions\Contracts\GenericException;
use App\Services\Recalculator\Processor\Processor;
use App\Services\Recalculator\ServiceException;
use Illuminate\Database\Eloquent\Model;
use Throwable;

final class FailedToRecalculateModel extends ServiceException implements GenericException {
    public function __construct(
        protected Processor $processor,
        protected ?Model $model,
        Throwable $previous = null,
    ) {
        parent::__construct('Failed to recalculate model properties.', $previous);

        $this->setContext([
            'processor' => $this->processor::class,
            'model'     => $this->model,
        ]);
    }
}
