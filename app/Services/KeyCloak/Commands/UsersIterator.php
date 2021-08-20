<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Commands;

use App\Services\DataLoader\Client\QueryIterator;
use App\Services\KeyCloak\Client\Client;
use Closure;
use Iterator;


class UsersIterator implements QueryIterator {
    protected ?Closure $beforeChunk = null;
    protected ?Closure $afterChunk  = null;
    protected ?string  $current     = null;
    protected ?int     $limit       = 0;
    protected int      $chunk       = 0;

    public function __construct(
        protected Client $client,
    ) {
        // empty
    }

    public function getLimit(): ?int {
        return $this->limit;
    }

    public function setLimit(?int $limit): static {
        $this->limit = $limit;

        return $this;
    }

    public function getChunkSize(): int {
        return $this->chunk;
    }

    public function setChunkSize(int $chunk): static {
        $this->chunk = $chunk;

        return $this;
    }

    public function getOffset(): string|int|null {
        return $this->offset;
    }

    public function setOffset(string|int|null $offset): static {
        $this->offset = $offset;

        return $this;
    }

    public function onBeforeChunk(?Closure $closure): static {
        $this->beforeChunk = $closure;

        return $this;
    }

    public function onAfterChunk(?Closure $closure): static {
        $this->afterChunk = $closure;

        return $this;
    }

    public function getIterator(): Iterator {
        // Prepare
        $index  = 0;
        $offset = 0;
        $chunk  = $this->chunk;
        $limit  = $this->limit;

        // Iterate
        do {
            $items   = $this->client->getUsers($limit, $offset);
            $offset += $chunk;

            foreach ($items as $item) {
                yield $index++ => $item;
            }
        } while ($items);
    }
}
