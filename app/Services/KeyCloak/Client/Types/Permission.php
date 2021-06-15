<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client\Types;

use App\Utils\JsonObject;

class Permission extends JsonObject {
    public string $id;
    public string $name;
    public string $containerId;
    public string $description;
    public bool   $clientRole;
    public bool   $composite;

    /**
     * @var array<string>
     */
    public array $attributes;
}
