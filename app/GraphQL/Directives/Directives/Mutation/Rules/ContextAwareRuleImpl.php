<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Rules;

use App\GraphQL\Directives\Directives\Mutation\Context\Context;

/**
 * @mixin ContextAwareRule
 */
trait ContextAwareRuleImpl {
    private Context $mutationContext;

    protected function hasMutationContext(): bool {
        return isset($this->mutationContext);
    }

    protected function getMutationContext(): Context {
        return $this->mutationContext;
    }

    /**
     * @template T of object
     *
     * @param class-string<T>|null $class
     *
     * @return ($class is null ? object|null : T|null)
     */
    protected function getMutationRoot(string $class = null): ?object {
        $model = null;

        if ($this->hasMutationContext()) {
            $context = $this->getMutationContext();

            do {
                $model   = $context->getContext()?->getRoot();
                $context = $context->getParent();
            } while ($context && $class !== null && !($model instanceof $class));
        }

        return $model;
    }

    /**
     * @return $this
     */
    public function setMutationContext(Context $context): self {
        $this->mutationContext = $context;

        return $this;
    }
}
