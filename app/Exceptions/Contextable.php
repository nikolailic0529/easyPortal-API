<?php declare(strict_types = 1);

namespace App\Exceptions;

interface Contextable {
    /**
     * @return array<mixed>
     */
    public function context(): array;
}
