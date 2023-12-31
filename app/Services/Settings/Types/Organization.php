<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

use App\Rules\Organization\Id;

class Organization extends StringType {
    /**
     * @inheritDoc
     */
    public function getValidationRules(): array {
        return [new Id()];
    }
}
