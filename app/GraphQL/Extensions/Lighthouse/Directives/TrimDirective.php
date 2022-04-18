<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\Lighthouse\Directives;

use Nuwave\Lighthouse\Schema\Directives\TrimDirective as LighthouseTrimDirective;

/**
 * Unfortunately Lighthouse ignored `TransformsRequest` middlewares. This is
 * leads to empty strings will not be converted to `null` and will pass any
 * validation if `required` rule is not set.
 *
 * @see https://laravel.com/docs/9.x/validation#implicit-rules
 * @see https://github.com/nuwave/lighthouse/issues/1459
 * @see https://github.com/laragraph/utils/issues/9
 */
class TrimDirective extends LighthouseTrimDirective {
    protected function transformLeaf(mixed $value): mixed {
        $value = parent::transformLeaf($value);

        if ($value === '') {
            $value = null;
        }

        return $value;
    }
}
