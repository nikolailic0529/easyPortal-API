<?php declare(strict_types = 1);

namespace App\Exceptions\GraphQL;

use App\Exceptions\Handler;
use GraphQL\Error\Error as GraphQLError;
use GraphQL\Error\FormattedError;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;

class ErrorFormatter {
    public function __construct(
        protected Repository $config,
        protected ExceptionHandler $handler,
    ) {
        // empty
    }

    /**
     * @return array<mixed>
     */
    public function __invoke(GraphQLError $error): array {
        $result = FormattedError::createFromException($error);

        if ($this->handler instanceof Handler) {
            $result['message'] = $this->handler->getExceptionMessage($error->getPrevious() ?? $error);

            if ($this->config->get('app.debug')) {
                $result['extensions']['debug'] = $this->handler->getExceptionData($error);
            }
        }

        return $result;
    }
}
