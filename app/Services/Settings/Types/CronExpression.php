<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

use App\Rules\CronExpression as CronExpressionRule;

class CronExpression extends Type {
    /**
     * @inheritDoc
     */
    public function getValidationRules(): array {
        return [new CronExpressionRule()];
    }
}
