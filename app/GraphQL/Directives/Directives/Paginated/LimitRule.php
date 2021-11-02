<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Validation\Rule;

use function __;
use function filter_var;

use const FILTER_VALIDATE_INT;

class LimitRule implements Rule {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return filter_var($value, FILTER_VALIDATE_INT) !== false
            && $value <= $this->getMaxValue();
    }

    public function message(): string {
        return __('validation.max.numeric', [
            'max' => $this->getMaxValue(),
        ]);
    }

    protected function getMaxValue(): int {
        $max = (int) $this->config->get('ep.pagination.limit.max');
        $max = $max > 0 ? $max : 1000;

        return $max;
    }
}
