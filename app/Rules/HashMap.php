<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

use function array_filter;
use function array_keys;
use function count;
use function is_array;
use function trans;

/**
 * Checks that value is `array<string, mixed>`.
 */
class HashMap implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return is_array($value)
            && count($value) === count(array_filter(array_keys($value), 'is_string'));
    }

    public function message(): string {
        return trans('validation.hash_map');
    }
}
