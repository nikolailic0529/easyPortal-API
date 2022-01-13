<?php declare(strict_types = 1);

namespace App\Services\Auth\Contracts;

interface Enableable {
    public function isEnabled(): bool;
}
