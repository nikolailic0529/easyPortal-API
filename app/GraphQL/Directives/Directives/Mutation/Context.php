<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation;

use Illuminate\Database\Eloquent\Model;

class Context {
    public function __construct(
        protected mixed $parent,
        protected ?Model $model,
    ) {
        // empty
    }

    public function getParent(): mixed {
        return $this->parent;
    }

    public function getModel(): ?Model {
        return $this->model;
    }
}
