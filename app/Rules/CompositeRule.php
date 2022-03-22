<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Rule;

use function array_reverse;
use function explode;

/**
 * Rule that allows creating Rule based on other validation rules.
 */
abstract class CompositeRule implements Rule {
    protected string|null $message = null;

    public function __construct(
        protected Factory $factory,
    ) {
        // empty
    }

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        [$attribute]   = array_reverse(explode('.', $attribute));
        $validator     = $this->factory->make([$attribute => $value], [$attribute => $this->getRules()]);
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
     * @return array<Rule|string>
     */
    abstract protected function getRules(): array;
}
