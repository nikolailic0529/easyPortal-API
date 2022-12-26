<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizer\Normalizers\DateTimeNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\TextNormalizer;
use App\Services\DataLoader\Schema\Type;
use App\Utils\JsonObject\JsonObjectNormalizer;
use Carbon\CarbonImmutable;

class CoverageEntry extends Type {
    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $coverageStartDate;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $coverageEndDate;

    public string $type;
    public string $status;

    #[JsonObjectNormalizer(TextNormalizer::class)]
    public ?string $description;

    public ?string $serviceSku;
}
