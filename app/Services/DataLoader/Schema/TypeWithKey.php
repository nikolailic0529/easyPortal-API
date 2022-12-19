<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

interface TypeWithKey {
    public function getKey(): string;
}
