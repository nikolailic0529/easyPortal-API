<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use App\Http\Controllers\Export\Selector;

use function is_scalar;
use function json_encode;

use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class Value implements Selector {
    public function __construct(
        protected string $property,
        protected int $index,
    ) {
        // empty
    }

    /**
     * @inheritdoc
     */
    public function fill(array $item, array &$row): void {
        $row[$this->index] = $this->value($item[$this->property] ?? null);
    }

    protected function value(mixed $value): string {
        $flags = JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;
        $value = $value === null || is_scalar($value) ? (string) $value : json_encode($value, $flags);

        return $value;
    }
}
