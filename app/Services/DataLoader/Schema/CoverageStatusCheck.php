<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Utils\JsonObject\JsonObjectArray;

class CoverageStatusCheck extends Type {
    public string $coverageStatus;
    public string $coverageStatusUpdatedAt;

    /**
     * @var array<CoverageEntry>
     */
    #[JsonObjectArray(CoverageEntry::class)]
    public array $coverageEntries;
}
