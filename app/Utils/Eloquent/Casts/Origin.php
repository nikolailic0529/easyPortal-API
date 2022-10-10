<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Support\Str;
use LogicException;

use function sprintf;
use function str_ends_with;

/**
 * The cast updates the calculated value (attribute without {@see Origin::SUFFIX}
 * suffix) when the attribute value is changed. Allows to reduce the number of
 * lines of the code.
 */
class Origin implements CastsInboundAttributes {
    protected const SUFFIX = '_origin';

    public function __construct() {
        // empty
    }

    /**
     * @inheritDoc
     */
    public function set($model, string $key, $value, array $attributes): mixed {
        if (str_ends_with($key, static::SUFFIX)) {
            $model->setAttribute(Str::beforeLast($key, static::SUFFIX), $value);
        } else {
            throw new LogicException(sprintf(
                'The `%s` should have `%s` suffix.',
                $key,
                static::SUFFIX,
            ));
        }

        return $value;
    }
}
