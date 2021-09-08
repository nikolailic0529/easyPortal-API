<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Exceptions\ApplicationException;
use App\Exceptions\TranslatedException;

abstract class GraphQLException extends ApplicationException implements TranslatedException {
    // empty
}
