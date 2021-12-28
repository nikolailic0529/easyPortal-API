<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use Illuminate\Database\Eloquent\Model;

abstract class MutationMutate extends MutationCall {
    protected const NAME = 'mutationMutate';

    protected function getRoot(Context $context): ?Model {
        if (!$context->getModel()) {
            throw new ObjectNotFound();
        }

        return parent::getRoot($context);
    }
}
