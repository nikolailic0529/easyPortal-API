<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client\Types;

class UserInput extends Input {
    public string $email;

    /**
     * @var array<string>
     */
    public array $groups;

    /**
     * @var array<string>
     */
    public array $clientRoles;
}
