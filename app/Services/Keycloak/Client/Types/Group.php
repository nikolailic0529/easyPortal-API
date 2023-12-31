<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Client\Types;

use App\Utils\JsonObject\JsonObject;
use App\Utils\JsonObject\JsonObjectArray;

class Group extends JsonObject {
    public string $id;
    public string $name;
    public string $path;

    /**
     * @var array<string>
     */
    public array $attributes;

    /**
     * @var array<string>
     */
    public array $access;

    /**
     * @var array<string>
     */
    public array $clientRoles;

    /**
     * @var array<string>
     */
    public array $realmRoles;

    /**
     * @var array<Group>
     */
    #[JsonObjectArray(Group::class)]
    public array $subGroups;
}
