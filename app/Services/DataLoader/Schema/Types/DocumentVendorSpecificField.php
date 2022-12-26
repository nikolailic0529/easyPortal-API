<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizers\StringNormalizer;
use App\Services\DataLoader\Schema\Type;
use App\Utils\JsonObject\JsonObjectNormalizer;

class DocumentVendorSpecificField extends Type {
    #[JsonObjectNormalizer(StringNormalizer::class)]
    public string $vendor;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $sar;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $ampId;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $said;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $groupId;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $groupDescription;
}
