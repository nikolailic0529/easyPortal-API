<?php declare(strict_types = 1);

namespace App\Exceptions;

use GraphQL\Error\Error as GraphQLError;
use GraphQL\Error\FormattedError;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Throwable;

class GraphQLHandler {
    public function __construct(
        protected Repository $config,
        protected ExceptionHandler $handler,
        protected Helper $helper,
    ) {
        // empty
    }

    /**
     * @return array<mixed>
     */
    public function __invoke(GraphQLError $error): array {
        $result = [
                'message' => $this->helper->getMessage($error->getPrevious() ?? $error),
            ] + FormattedError::createFromException($error);

        if ($this->config->get('app.debug')) {
            $handler = $this->handler instanceof ContextProvider
                ? $this->handler
                : new class() implements ContextProvider {
                    /**
                     * @inheritDoc
                     */
                    public function getExceptionContext(Throwable $exception): array {
                        return [];
                    }
                };

            $result['extensions']['stack'] = $this->helper->getTrace($error, $handler);
        }

        return $result;
    }
}
