<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Rules;

use App\GraphQL\Directives\Directives\Mutation\Context\Context;
use Illuminate\Database\Eloquent\Model;

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

    protected function getMutationRoot(): ?Model {
        return $this->hasMutationContext()
            ? $this->getMutationContext()->getRoot()
            : null;
    }

    /**
     * @return $this
     */
    public function setMutationContext(Context $context): self {
        $this->mutationContext = $context;

        return $this;
    }
}
