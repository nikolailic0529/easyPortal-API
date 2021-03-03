<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

/**
 * @internal
 */
class Location extends Type {
    public string $zip;
    public string $address;
    public string $city;
    public string $locationType;
}
