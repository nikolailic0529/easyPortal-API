<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth\Organization;

use App\Utils\JsonObject;

class AuthorizeInput extends JsonObject {
    public string $code;
    public string $state;
}
