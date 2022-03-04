<?php declare(strict_types = 1);

namespace App\Services\Search\Exceptions;

use App\Services\Search\Processor\Processor;
use App\Services\Search\ServiceException;
use Illuminate\Database\Eloquent\Model;
use Psr\Log\LogLevel;
use Throwable;

class FailedToIndex extends ServiceException {
    public function __construct(
        protected Processor $processor,
        protected ?Model $model,
        Throwable $previous = null,
    ) {
        parent::__construct('Failed to add model into the search index.', $previous);

        $this->setLevel(LogLevel::WARNING);
        $this->setContext([
            'processor' => $this->processor::class,
            'model'     => $this->model,
        ]);
    }
}
