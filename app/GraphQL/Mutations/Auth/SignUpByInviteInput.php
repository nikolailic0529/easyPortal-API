<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Utils\JsonObject\JsonObject;

class SignUpByInviteInput extends JsonObject {
    public string $given_name;
    public string $family_name;
    public string $password;
}
