<?php declare(strict_types = 1);

namespace App\Services\Queue\Contracts;

interface NamedJob {
    public function displayName(): string;
}
