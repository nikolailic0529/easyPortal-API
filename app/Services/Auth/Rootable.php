<?php declare(strict_types = 1);

namespace App\Services\Auth;

interface Rootable {
    public function isRootable(): bool;
}
