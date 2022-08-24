<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Exceptions\Contracts\TranslatedException;
use GraphQL\Error\ClientAware;

abstract class GraphQLException extends GraphQLError implements TranslatedException, ClientAware {
    public function isClientSafe(): bool {
        return true;
    }

    public function getCategory(): string {
        return 'application';
    }
}
