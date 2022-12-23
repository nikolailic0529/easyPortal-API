<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Services\DataLoader\Normalizer\Normalizers\DateTimeNormalizer;
use App\Utils\JsonObject\JsonObjectArray;
use App\Utils\JsonObject\JsonObjectNormalizer;
use Carbon\CarbonImmutable;

class CoverageStatusCheck extends Type {
    public string $coverageStatus;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public CarbonImmutable $coverageStatusUpdatedAt;

    /**
     * @var array<CoverageEntry>
     */
    #[JsonObjectArray(CoverageEntry::class)]
    public array $coverageEntries;
}
