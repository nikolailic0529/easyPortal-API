<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

use function validator;

/**
 * Rule that allows creating Rule based on other validation rules.
 */
abstract class CompositeRule implements Rule {
    protected string|null $message = null;

    public function __construct() {
        // empty
    }

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        $validator     = validator([$attribute => $value], [$attribute => $this->getRules()]);
        $failed        = $validator->fails();
        $this->message = $failed
            ? $validator->errors()->first($attribute)
            : null;

        return !$failed;
    }

    public function message(): string {
        return (string) $this->message;
    }

    /**
     * @return array<\Illuminate\Contracts\Validation\Rule|string>
     */
    abstract protected function getRules(): array;
}
