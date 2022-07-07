<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Locale;

use App\Utils\JsonObject\JsonObject;

class UpdateTranslationInput extends JsonObject {
    public string  $key;
    public ?string $value;
}
