<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Schema\Type;

use function end;

class LastIdBasedIterator extends QueryIterator {
    protected ?string $lastId = null;

    public function getLastId(): ?string {
        return $this->lastId;
    }

    public function lastId(?string $lastId): static {
        $this->lastId = $lastId;

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
    protected function isLastChunk(array $items): bool {
        $last   = end($items);
        $isLast = true;

        if ($last instanceof Type && isset($last->id)) {
            $this->lastId($last->id);

            $isLast = false;
        }

        return $isLast;
    }
}
