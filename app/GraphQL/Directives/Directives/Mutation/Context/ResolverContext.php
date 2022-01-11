<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Context;

use Illuminate\Database\Eloquent\Model;

class ResolverContext extends Context {
    public function __construct(?Context $parent, ?Model $root) {
        parent::__construct($parent, $root);
    }
}
