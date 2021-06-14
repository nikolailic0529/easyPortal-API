<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Schema\Type;

use function end;

class LastIdBasedIterator extends QueryIterator {
    protected ?string $lastId = null;

    public function lastId(string $offset): static {
        $this->lastId = $offset;

        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function getQueryParams(): array {
        return [
            'lastId' => $this->lastId,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function chunkProcessed(array $items): bool {
        $last     = end($items);
        $continue = false;

        if ($last instanceof Type && isset($last->id)) {
            $this->lastId($last->id);

            $continue = true;
        }

        return $continue;
    }
}
