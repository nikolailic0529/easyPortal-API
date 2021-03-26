<?php declare(strict_types = 1);

namespace App\GraphQL;

use Closure;
use Error;
use Exception;
use GraphQL\Error\Error as GraphQLError;
use GraphQL\Error\FormattedError;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Nuwave\Lighthouse\Exceptions\RateLimitException;
use Nuwave\Lighthouse\Exceptions\RendersErrorsExtensions;
use Nuwave\Lighthouse\Exceptions\ValidationException;
use Nuwave\Lighthouse\Execution\ErrorHandler as LighthouseErrorHandler;

use function __;

class ErrorHandler implements LighthouseErrorHandler {
    public function __construct() {
        // empty
    }

    /**
     * @inheritDoc
     */
    public function __invoke(?GraphQLError $error, Closure $next): ?array {
        if ($error?->getPrevious() instanceof AuthenticationException) {
            $error = $this->setErrorMessage($error, __('errors.unauthenticated'));
        }

        if ($error?->getPrevious() instanceof AuthorizationException) {
            $error = $this->setErrorMessage($error, __('errors.unauthorized'));
        }

        if ($error?->getPrevious() instanceof RateLimitException) {
            $error = $this->setErrorMessage($error, __('errors.too_many_requests'));
        }

        if ($error?->getPrevious() instanceof ValidationException) {
            $error = $this->setErrorMessage($error, __('errors.validation_failed'));
        }

        if ($error?->getPrevious() instanceof Error) {
            FormattedError::setInternalErrorMessage(__('errors.server_error'));
            $error = $this->setErrorMessage($error, __('errors.server_error'));
        }

        return $next($error);
    }

    protected function setErrorMessage(Exception $error, string $message): Exception {
        return new GraphQLError(
            $message,
            $error->getNodes(),
            $error->getSource(),
            $error->getPositions(),
            $error->getPath(),
            $error->getPrevious(),
            $error->getPrevious() instanceof RendersErrorsExtensions
                ? $error->getPrevious()->extensionsContent()
                : [],
        );
    }
}
