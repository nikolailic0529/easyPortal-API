<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Rules;

use App\GraphQL\Directives\Directives\Mutation\Context\Context;

interface ContextAwareRule {
    /**
     * @return $this
     */
    public function setMutationContext(Context $context): self;
}
