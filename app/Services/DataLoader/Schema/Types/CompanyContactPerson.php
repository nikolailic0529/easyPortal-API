<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizer\Normalizers\StringNormalizer;
use App\Services\DataLoader\Schema\Type;
use App\Utils\JsonObject\JsonObjectNormalizer;

class CompanyContactPerson extends Type {
    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $phoneNumber;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $name;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $vendor;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $mail;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public string $type;
}
