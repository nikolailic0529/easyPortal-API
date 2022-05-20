<?php declare(strict_types = 1);

namespace App\Services\Search\Exceptions;

use App\Exceptions\Contracts\GenericException;
use App\Services\Search\Processors\FulltextIndex;
use App\Services\Search\Processors\FulltextProcessor;
use App\Services\Search\ServiceException;
use Throwable;

use function sprintf;

class FailedToRebuildFulltextIndexes extends ServiceException implements GenericException {
    public function __construct(
        protected FulltextProcessor $processor,
        protected FulltextIndex $index,
        Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                'Failed to rebuild FULLTEXT index `%s` for `%s`.',
                $this->index->getName(),
                $this->index->getModel(),
            ),
            $previous,
        );
    }
}
