<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Organization\User;

use App\Utils\JsonObject;

class InviteInput extends JsonObject {
    public string  $email;
    public string  $role_id;
    public ?string $team_id;
}
