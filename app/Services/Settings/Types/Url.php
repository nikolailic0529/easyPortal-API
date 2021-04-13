<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

class Url extends Type {
    /**
     * @inheritDoc
     */
    public function getValidationRules(): array {
        return ['url'];
    }
}
