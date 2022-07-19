<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

class Email extends StringType {
    /**
     * @inheritDoc
     */
    public function getValidationRules(): array {
        return ['email'];
    }
}
