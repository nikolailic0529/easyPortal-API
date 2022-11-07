<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use App\Http\Controllers\Export\Selector;

use function is_scalar;
use function is_string;
use function json_encode;
use function trim;

use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

abstract class Value implements Selector {
    protected function value(mixed $value): string|float|int|bool|null {
        if ($value === null || is_scalar($value)) {
            if (is_string($value)) {
                $value = trim($value);
            } else {
                // as is
            }
        } else {
            $value = json_encode(
                $value,
                JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );
        }

        return $value;
    }
}
