<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Context;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

class BuilderContext extends Context {
    public function __construct(
        ?Context $parent,
        ?Model $root,
        private EloquentBuilder $builder,
    ) {
        parent::__construct($parent, $root, $this->builder->getModel()->getMorphClass());
    }

    public function getBuilder(): EloquentBuilder {
        return $this->builder;
    }
}
