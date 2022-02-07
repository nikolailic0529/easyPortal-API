<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client\Types;

use App\Utils\JsonObject\JsonObject;

class Role extends JsonObject {
    public string  $id;
    public string  $name;
    public string  $containerId;
    public ?string $description;
    public bool    $clientRole;
    public bool    $composite;
    public string  $path;

    /**
     * @var array<string>
     */
    public array $attributes;

    /**
     * @var array<\App\Services\KeyCloak\Client\Types\RoleComposite>
     */
    public array $composites;
}
