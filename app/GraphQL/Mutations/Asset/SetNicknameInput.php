<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Asset;

use App\Utils\JsonObject\JsonObject;

class SetNicknameInput extends JsonObject {
    public ?string $nickname;
}
