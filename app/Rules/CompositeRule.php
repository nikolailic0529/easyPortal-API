<?php declare(strict_types = 1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Rule;

use function assert;
use function data_set;
use function is_array;

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
        $data          = $this->getData($attribute, $value);
        $validator     = $this->factory->make($data, [$attribute => $this->getRules()]);
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

    /**
     * @return array<mixed>
     */
    protected function getData(string $attribute, mixed $value): array {
        $data = [];
        $data = data_set($data, $attribute, $value);

        assert(is_array($data));

        return $data;
    }
}
