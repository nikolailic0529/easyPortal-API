<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Client\Types;

use App\Utils\JsonObject\JsonObject;
use App\Utils\JsonObject\JsonObjectArray;

class User extends JsonObject {
    public string $id;
    public int    $createdTimestamp;
    public string $username;
    public ?string $firstName;
    public ?string $lastName;
    public string $email;
    public bool   $enabled;
    public bool   $totp;
    public bool   $emailVerified;
    public int    $notBefore;

    /**
     * @var array<string>
     */
    public array $disableableCredentialTypes;

    /**
     * @var array<string>
     */
    public array $requiredActions;

    /**
     * @var array<string>
     */
    public array $attributes = [];

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
    public array $groups;

    /**
     * @var array<Credential>
     */
    #[JsonObjectArray(Credential::class)]
    public array $credentials;
}
