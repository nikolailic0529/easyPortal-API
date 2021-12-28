<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\InvalidContext;
use Illuminate\Database\Eloquent\Model;

abstract class MutationCreate extends MutationCall {
    protected const NAME = 'mutationCreate';

    protected function getRoot(Context $context): ?Model {
        if ($context->getModel()) {
            throw new InvalidContext();
        }

        return parent::getRoot($context);
    }
}
