<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Context;

use Illuminate\Database\Eloquent\Model;

abstract class Context {
    public function __construct(
        private ?Context $parent,
        private ?object $root,
        private ?string $model = null,
    ) {
        // empty
    }

    public function getParent(): ?Context {
        return $this->parent;
    }

    public function getRoot(): ?object {
        return $this->root;
    }

    public function getModel(): ?string {
        return $this->model ?? ($this->root instanceof Model ? $this->root->getMorphClass() : null);
    }

    public function getContext(): ?Context {
        $context = null;
        $current = $this;

        while ($current) {
            if ($current instanceof EmptyContext) {
                $current = $current->getParent();
            } else {
                $context = $current;
                break;
            }
        }

        return $context;
    }
}
