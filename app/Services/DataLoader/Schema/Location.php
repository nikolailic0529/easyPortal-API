<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class Location extends Type {
    public ?string $zip;
    public ?string $address;
    public ?string $city;
    public ?string $locationType;
    public ?string $country;
    public ?string $latitude;
    public ?string $longitude;
    public ?string $countryCode;
}
