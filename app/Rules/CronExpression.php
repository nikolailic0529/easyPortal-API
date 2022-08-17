<?php declare(strict_types = 1);

namespace App\Rules;

use Cron\CronExpression as Cron;
use Illuminate\Contracts\Validation\Rule;

use function is_string;
use function trans;

/**
 * Cron Expression.
 *
 * @see https://en.wikipedia.org/wiki/Cron
 */
class CronExpression implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return is_string($value) && Cron::isValidExpression($value);
    }

    public function message(): string {
        return trans('validation.cron');
    }
}
