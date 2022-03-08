<?php declare(strict_types = 1);

namespace App\Services\Search\Exceptions;

use App\Exceptions\Contracts\GenericException;
use App\Services\Search\Processor\Processor;
use App\Services\Search\ServiceException;
use Illuminate\Database\Eloquent\Model;
use Psr\Log\LogLevel;
use Throwable;

use function sprintf;

class FailedToIndex extends ServiceException implements GenericException {
    public function __construct(
        protected Processor $processor,
        protected Model $model,
        Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                'Failed to add %s `%s` into the search index.',
                $this->model->getMorphClass(),
                $this->model->getKey(),
            ),
            $previous,
        );

        $this->setLevel(LogLevel::WARNING);
        $this->setContext([
            'processor' => $this->processor::class,
        ]);
    }
}
