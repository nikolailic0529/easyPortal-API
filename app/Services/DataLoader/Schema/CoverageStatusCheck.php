<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class CoverageStatusCheck extends Type {
    public string $coverageStatus;
    public string $coverageStatusUpdatedAt;

    /**
     * @var array<\App\Services\DataLoader\Schema\CoverageEntry>
     */
    public array $coverageEntries;
}
