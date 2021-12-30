<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation;

use App\GraphQL\Directives\Directives\Mutation\Context\Context;
use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;

abstract class MutationMutate extends MutationCall {
    protected const NAME = 'mutationMutate';

    protected function getContext(Context $context): ?Context {
        if (!$context->getRoot()) {
            throw new ObjectNotFound($context->getModel());
        }

        return $context;
    }
}
