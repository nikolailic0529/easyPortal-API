<?php declare(strict_types = 1);

namespace App\Services\Search\Exceptions;

use App\Exceptions\Contracts\GenericException;
use App\Services\Search\Processors\FulltextProcessor;
use App\Services\Search\ServiceException;
use Illuminate\Database\Eloquent\Model;
use Throwable;

use function sprintf;

class FailedToRebuildFulltextIndexes extends ServiceException implements GenericException {
    /**
     * @param class-string<Model> $model
     */
    public function __construct(
        protected FulltextProcessor $processor,
        protected string $model,
        Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                'Failed to rebuild FULLTEXT indexes for `%s`.',
                $this->model,
            ),
            $previous,
        );
    }
}
