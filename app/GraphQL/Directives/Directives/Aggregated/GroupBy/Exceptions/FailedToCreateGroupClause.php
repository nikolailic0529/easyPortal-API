<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Aggregated\GroupBy\Exceptions;

use App\GraphQL\Directives\Directives\Aggregated\AggregatedException;
use App\GraphQL\GraphQLError;
use Throwable;

use function sprintf;

class FailedToCreateGroupClause extends GraphQLError implements AggregatedException {
    public function __construct(
        protected string $type,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Impossible to create GroupBy Clause for `%s`.',
            $this->type,
        ), $previous);
    }
}
