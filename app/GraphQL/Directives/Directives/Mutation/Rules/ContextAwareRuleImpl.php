<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Rules;

use App\GraphQL\Directives\Directives\Mutation\Context\Context;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements ContextAwareRule
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
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    protected function getMutationRoot(string $class = null): ?Model {
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
