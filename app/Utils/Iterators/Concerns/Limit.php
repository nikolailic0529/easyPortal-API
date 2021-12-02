<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Concerns;

trait Limit {
    private ?int $limit = null;

    public function getLimit(): ?int {
        return $this->limit;
    }

    public function setLimit(?int $limit): static {
        $this->limit = $limit;

        return $this;
    }
}
