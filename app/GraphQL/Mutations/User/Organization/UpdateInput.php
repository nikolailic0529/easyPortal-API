<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\User\Organization;

use App\Utils\JsonObject\JsonObject;

class UpdateInput extends JsonObject {
    public bool   $enabled;
    public string $role_id;
    public ?string $team_id;
}
