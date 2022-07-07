<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Locale;

use App\Utils\JsonObject\JsonObject;
use App\Utils\JsonObject\JsonObjectArray;

class UpdateInput extends JsonObject {
    /**
     * @var array<UpdateTranslationInput>|null
     */
    #[JsonObjectArray(type: UpdateTranslationInput::class)]
    public ?array $translations = null;
}
