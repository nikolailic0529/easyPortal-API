<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Rules;

use App\GraphQL\Directives\Directives\Mutation\Context\Context;

/**
 * @implements \App\GraphQL\Directives\Directives\Mutation\Rules\ContextAwareRule
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
     * @return $this
     */
    public function setMutationContext(Context $context): self {
        $this->mutationContext = $context;

        return $this;
    }
}
