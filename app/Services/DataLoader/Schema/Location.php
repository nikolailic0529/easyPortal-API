<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Services\DataLoader\Normalizer\Normalizers\StringNormalizer;
use App\Utils\JsonObject\JsonObjectNormalizer;

class Location extends Type {
    public ?string $zip;
    public ?string $address;
    public ?string $city;
    public ?string $locationType;
    public ?string $country;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $latitude;

    #[JsonObjectNormalizer(StringNormalizer::class)]
    public ?string $longitude;

    public ?string $countryCode;
}
