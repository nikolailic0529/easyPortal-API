<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizers\DateTimeNormalizer;
use App\Services\DataLoader\Normalizers\StringNormalizer;
use App\Services\DataLoader\Normalizers\TextNormalizer;
use App\Services\DataLoader\Schema\Type;
use App\Utils\JsonObject\JsonObjectNormalizer;
use Carbon\CarbonImmutable;

class CoverageEntry extends Type {
    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $coverageStartDate;

    #[JsonObjectNormalizer(DateTimeNormalizer::class)]
    public ?CarbonImmutable $coverageEndDate;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public string $type;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public string $status;

    #[JsonObjectNormalizer(TextNormalizer::class)]
    public ?string $description;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $serviceSku;
}
