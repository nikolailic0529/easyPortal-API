<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Contracts;

interface Limitable {
    public function getLimit(): ?int;

    public function setLimit(?int $limit): static;
}
