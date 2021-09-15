<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

class Email extends Type {
    /**
     * @inheritDoc
     */
    public function getValidationRules(): array {
        return ['email'];
    }
}
