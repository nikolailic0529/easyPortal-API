<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation;

use App\GraphQL\Directives\Directives\Mutation\Context\Context;
use App\GraphQL\Directives\Directives\Mutation\Context\EmptyContext;
use App\GraphQL\Directives\Directives\Mutation\Exceptions\InvalidContext;

abstract class MutationCreate extends MutationCall {
    protected const NAME = 'mutationCreate';

    protected function getContext(Context $context): ?Context {
        if (!($context instanceof EmptyContext)) {
            throw new InvalidContext();
        }

        return parent::getContext($context);
    }
}
