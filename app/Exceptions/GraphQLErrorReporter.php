<?php declare(strict_types = 1);

namespace App\Exceptions;

use Closure;
use GraphQL\Error\Error;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Nuwave\Lighthouse\Execution\ErrorHandler;

class GraphQLErrorReporter implements ErrorHandler {
    public function __construct(
        protected ExceptionHandler $handler,
    ) {
        // empty
    }

    /**
     * @inheritDoc
     */
    public function __invoke(?Error $error, Closure $next): ?array {
        if ($error && !$error->isClientSafe()) {
            $this->handler->report($error);
        }

        return $next($error);
    }
}
