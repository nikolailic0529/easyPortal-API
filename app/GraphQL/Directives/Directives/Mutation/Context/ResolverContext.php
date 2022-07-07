<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Context;

class ResolverContext extends Context {
    public function __construct(?Context $parent, ?object $root) {
        parent::__construct($parent, $root);
    }
}
