<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizer\Normalizers\DateTimeNormalizer;
use App\Services\DataLoader\Schema\Type;
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
