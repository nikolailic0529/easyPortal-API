<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Schema\TypeWithId;

use function end;

class LastIdBasedIterator extends QueryIteratorImpl {
    /**
     * @inheritDoc
     */
    protected function getQueryParams(): array {
        return [
            'lastId' => $this->getOffset(),
        ];
    }

    /**
     * @param array<mixed> $items
     */
    protected function chunkProcessed(array $items): bool {
        $last     = end($items);
        $continue = false;

        if ($last instanceof TypeWithId && isset($last->id)) {
            $this->setOffset($last->id);

            $continue = true;
        }

        return parent::chunkProcessed($items)
            && $continue;
    }
}
