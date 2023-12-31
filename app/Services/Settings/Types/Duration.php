<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

use App\Rules\Duration as DurationRule;

class Duration extends StringType {
    /**
     * @inheritDoc
     */
    public function getValidationRules(): array {
        return [new DurationRule()];
    }
}
