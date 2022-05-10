<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Contracts;

interface Offsetable {
    public function getOffset(): string|int|null;

    public function setOffset(string|int|null $offset): static;
}
