<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

use function is_string;
use function preg_match;
use function trans;

/**
 * ISO 8601 Durations
 *
 * @see https://en.wikipedia.org/wiki/ISO_8601#Durations
 * @see https://www.php.net/manual/en/dateinterval.construct.php
 * @see https://stackoverflow.com/questions/32044846/regex-for-iso-8601-durations
 */
class Duration implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return is_string($value) && preg_match(
            '/^P(?!$)(\d+Y)?(\d+M)?(\d+W)?(\d+D)?(T(?=\d)(\d+H)?(\d+M)?(\d+S)?)?$/',
            $value,
        );
    }

    public function message(): string {
        return trans('validation.duration');
    }
}
