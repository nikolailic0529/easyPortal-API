<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Rules;

use App\GraphQL\Directives\Directives\Mutation\Context\Context;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;

abstract class Rule extends BaseDirective {
    abstract public function validate(Context $context, mixed $value): bool;
}
