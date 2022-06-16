<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Exceptions\Contracts\GenericException;
use App\Services\DataLoader\Client\Query;
use Psr\Log\LogLevel;
use Throwable;

use function array_merge;

final class FailedToProcessChunkItem extends FailedToProcessObject implements GenericException {
    /**
     * @param Query<mixed> $query
     */
    public function __construct(
        protected Query $query,
        protected mixed $item,
        Throwable $previous = null,
    ) {
        parent::__construct('Failed to process chunk item.', $previous);

        $this->setLevel(LogLevel::ERROR);
        $this->setContext([
            'selector'  => $this->query->getSelector(),
            'graphql'   => $this->query->getQuery(),
            'variables' => array_merge($this->query->getVariables(), (array) $this->query->getLastVariables()),
            'item'      => $this->item,
        ]);
    }

    public function getItem(): mixed {
        return $this->item;
    }

    /**
     * @return Query<mixed>
     */
    public function getQuery(): Query {
        return $this->query;
    }
}
