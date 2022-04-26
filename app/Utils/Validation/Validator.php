<?php declare(strict_types = 1);

namespace App\Utils\Validation;

use Illuminate\Support\Arr;
use Illuminate\Validation\Validator as IlluminateValidator;

use function is_string;
use function mb_strtolower;

/**
 * According to Laravel documentation:
 *
 * > By default, when an attribute being validated is not present or
 * > contains an empty string, normal validation rules, including
 * > custom rules, are not run
 *
 * The problems are:
 * 1. In GraphQL the empty string this is a valid value, and it is not possible
 *    to convert it to `null` safely, e.g. when `Type!` because it will allow
 *    `null` where it is unexpected; Without converting empty strings will pass
 *    all (non-explicit) validation rules;
 * 2. There is no way to allow `nullable|required` that is useful for GraphQL
 *    to avoid adding custom "required" rule;
 *
 * Differences with Laravel:
 * * Empty strings will be validated
 *      ```php
 *      Validator::make(['value' => ''], ['value' => ['size:2']])->passes();
 *          // `true` in Laravel
 *          // `false` in App
 *      ```
 * * Nullable + Required is allowed
 *      ```php
 *      Validator::make(['value' => null], ['value' => ['nullable', 'required']])->passes();
 *          // `false` in Laravel
 *          // `true` in App
 *      ```
 * * Validation will be stopped for `null` after `nullable` rule
 *
 * @see https://github.com/fakharanwar/easyPortal-API/issues/751
 * @see https://laravel.com/docs/validation#implicit-rules
 * @see https://github.com/nuwave/lighthouse/issues/1459#issuecomment-1102248865
 * @see Validator::presentOrRuleIsImplicit()
 */
class Validator extends IlluminateValidator {
    protected function presentOrRuleIsImplicit(mixed $rule, mixed $attribute, mixed $value): bool {
        // (1) Empty strings should process like non-empty
        return $this->validatePresent($attribute, $value)
            || $this->isImplicit($rule);
    }

    protected function shouldStopValidating(mixed $attribute): bool {
        // (2) If value is `null` and it is allowed, validation can be stopped.
        $value = Arr::get($this->data, $attribute, 0);
        $rule  = isset($this->currentRule) && is_string($this->currentRule)
            ? mb_strtolower($this->currentRule)
            : null;

        return parent::shouldStopValidating($attribute)
            || ($rule === 'nullable' && $value === null);
    }
}
