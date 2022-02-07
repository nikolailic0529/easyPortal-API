<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client\Types;

use App\Utils\JsonObject\JsonObject;

class Credential extends JsonObject {
    public ?string $algorithm;
    public ?string $device;
    public ?string $salt;
    public ?string $type;
    public ?string $value;
    public ?int    $counter;
    public ?int    $createdDate;
    public ?int    $digits;
    public ?int    $period;
    public ?bool   $hashIterations;
    public ?bool   $hashedSaltedValue;
    public ?bool   $temporary;

    /**
     * @var array<string>
     */
    public ?array $config;
}
