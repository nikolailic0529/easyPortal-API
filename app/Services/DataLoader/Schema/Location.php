<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use App\Services\DataLoader\Utils\JsonFactory;

class Location extends JsonFactory {
    public string $zip;
    public string $address;
    public string $city;
    public string $locationType;
}
