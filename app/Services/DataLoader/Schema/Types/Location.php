<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use App\Services\DataLoader\Normalizer\Normalizers\StringNormalizer;
use App\Services\DataLoader\Schema\Type;
use App\Utils\JsonObject\JsonObjectNormalizer;

class Location extends Type {
    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $zip;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $address;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $city;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $locationType;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $country;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $latitude;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $longitude;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $countryCode;
}
