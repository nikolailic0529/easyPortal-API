<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Context;

class EmptyContext extends Context {
    public function __construct(
        ?Context $parent,
        ?string $model = null,
    ) {
        parent::__construct($parent, null, $model);
    }
}
