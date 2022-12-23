<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Services\DataLoader\Normalizer\Normalizers\TextNormalizer;
use App\Utils\JsonObject\JsonObjectNormalizer;

class CoverageEntry extends Type {
    public ?string $coverageStartDate;
    public ?string $coverageEndDate;
    public string  $type;
    public string  $status;

    #[JsonObjectNormalizer(TextNormalizer::class)]
    public ?string $description;

    public ?string $serviceSku;
}
