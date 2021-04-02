<?php declare(strict_types = 1);

namespace App\Exceptions;

use Error;
use GraphQL\Error\Error as GraphQLError;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Arr;
use Nuwave\Lighthouse\Exceptions\DefinitionException as GraphQLDefinitionException;
use Nuwave\Lighthouse\Exceptions\RateLimitException;
use Nuwave\Lighthouse\Exceptions\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

use function __;
use function collect;

class Helper {
    /**
     * @var array<int, string>
     */
    protected array $http = [
        401 => 'errors.unauthorized',
        403 => 'errors.forbidden',
        404 => 'errors.not_found',
        419 => 'errors.page_expired',
        429 => 'errors.too_many_requests',
        500 => 'errors.server_error',
        503 => 'errors.service_unavailable',
    ];

    public function __construct(
        protected LoggerInterface $logger,
    ) {
        // empty
    }

    public function getMessage(Throwable $error): string {
        // Determine key
        $key = null;

        if ($error instanceof TokenMismatchException) {
            $key = 'errors.page_expired';
        } elseif ($error instanceof AuthenticationException) {
            $key = 'errors.unauthenticated';
        } elseif ($error instanceof AuthorizationException) {
            $key = 'errors.unauthorized';
        } elseif ($error instanceof RateLimitException) {
            $key = 'errors.too_many_requests';
        } elseif ($error instanceof SuspiciousOperationException) {
            $key = 'errors.not_found';
        } elseif ($error instanceof ValidationException) {
            $key = 'errors.validation_failed';
        } elseif ($error instanceof RecordsNotFoundException) {
            $key = 'errors.not_found';
        } elseif ($error instanceof HttpExceptionInterface) {
            $key = $this->http[$error->getStatusCode()]
                ?? "errors.http.{$error->getStatusCode()}";
        } elseif ($error instanceof GraphQLDefinitionException) {
            $key = 'errors.graphql.schema_broken';
        } elseif ($error instanceof GraphQLError) {
            $key = 'errors.graphql.error';
        } elseif ($error instanceof Error) {
            $key = 'errors.server_error';
        } else {
            // empty
        }

        // Translate
        $message = $key ? __($key) : $error->getMessage();

        if (!$key || $key === $message) {
            $message = $error->getMessage();

            $this->logger->notice('Missing translation.', [
                'key'   => $key,
                'error' => $error,
            ]);
        }

        // Return
        return $message;
    }

    /**
     * @return array<mixed>
     */
    public function getTrace(Throwable $error): array {
        $stack = [];

        do {
            $stack[] = [
                'exception' => $error::class,
                'message'   => $error->getMessage(),
                'file'      => $error->getFile(),
                'line'      => $error->getLine(),
                'trace'     => collect($error->getTrace())->map(static function (array $trace): array {
                    return Arr::except($trace, ['args']);
                })->all(),
            ];
            $error   = $error->getPrevious();
        } while ($error);

        return $stack;
    }
}
