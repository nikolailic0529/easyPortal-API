<?php declare(strict_types = 1);

namespace App\Services\Auth\Contracts;

interface Rootable {
    public function isRoot(): bool;
}
