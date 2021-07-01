<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client\Types;

use App\Utils\JsonObject;

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
    public array $groups;

    /**
     * @var array<\App\Services\KeyCloak\Client\Types\Credential>
     */
    public array $credentials;
}
