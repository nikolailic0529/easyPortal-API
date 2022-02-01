<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org\Role;

use App\Utils\JsonObject\JsonObject;

class UpdateInput extends JsonObject {
    public ?string $name;

    /**
     * @var array<string>
     */
    public ?array $permissions;
}
