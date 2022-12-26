<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizers\StringNormalizer;
use App\Services\DataLoader\Schema\Type;
use App\Utils\JsonObject\JsonObjectNormalizer;

class CompanyType extends Type {
    #[JsonObjectNormalizer(StringNormalizer::class)]
    public string $type;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public string $status;
}
