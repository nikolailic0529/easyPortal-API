<?php declare(strict_types = 1);

namespace App\GraphQL\Objects;

use App\Utils\JsonObject\JsonObject;

class Locale extends JsonObject {
    public string $name;
}
