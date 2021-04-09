<?php declare(strict_types = 1);

namespace App\Jobs;

interface NamedJob {
    public function displayName(): string;
}
