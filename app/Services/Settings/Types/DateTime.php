<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

use App\Rules\DateTime as DateTimeRule;

class DateTime extends StringType {
    /**
     * @inheritDoc
     */
    public function getValidationRules(): array {
        return [new DateTimeRule()];
    }
}
