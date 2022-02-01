<?php declare(strict_types = 1);

namespace App\GraphQL\Objects;

use App\Utils\JsonObject\JsonObject;

class StringValue extends JsonObject {
    public string $value;
}
