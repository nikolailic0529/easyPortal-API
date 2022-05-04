<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Utils\JsonObject\JsonObject;

class CompositeOperationState extends JsonObject {
    public ?string $name  = null;
    public ?State  $state = null;
}
