<?php declare(strict_types = 1);

namespace App\Services\Search\Exceptions;

use App\Services\Search\ServiceException;
use Illuminate\Database\Eloquent\Model;
use Psr\Log\LogLevel;
use Throwable;

use function sprintf;

class FailedToIndex extends ServiceException {
    public function __construct(Model $model, Throwable $previous = null) {
        parent::__construct(sprintf(
            'Failed to add model `%s#%s` into the search index.',
            $model::class,
            $model->getKey(),
        ), $previous);

        $this->setLevel(LogLevel::WARNING);
    }
}
