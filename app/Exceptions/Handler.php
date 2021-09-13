<?php declare(strict_types = 1);

namespace App\Exceptions;

use GraphQL\Error\Error as GraphQLError;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Nuwave\Lighthouse\Exceptions\DefinitionException as GraphQLDefinitionException;
use Nuwave\Lighthouse\Exceptions\RateLimitException;
use Nuwave\Lighthouse\Exceptions\ValidationException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

use function array_merge;
use function is_null;
use function reset;
use function rtrim;
use function trim;

class Handler extends ExceptionHandler {
    /**
     * A list of the exception types that are not reported.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $dontReport = [];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void {
        $this->reportable(function (Throwable $exception): void {
            $this->dispatchException($exception);
        });
        $this->reportable(function (Throwable $exception): bool {
            return !$this->reportException($exception);
        });
    }

    /**
     * @return array<mixed>
     */
    protected function convertExceptionToArray(Throwable $e): array {
        $config = $this->container->make(Repository::class);
        $array  = $this->getExceptionContext($e);

        if (!$config->get('app.debug')) {
            $array = Arr::only($array, ['message']);
        }

        return $array;
    }

    /**
     * @return array<mixed>
     */
    protected function exceptionContext(Throwable $e): array {
        $context = parent::exceptionContext($e);

        if ($e instanceof ApplicationException) {
            $context = array_merge($context, $e->getContext());
        }

        return $context;
    }

    protected function reportException(Throwable $exception): bool {
        // Get logger (channel)
        $logger = null;

        try {
            $logger = $this->getExceptionLogger($exception);
        } catch (BindingResolutionException) {
            // no action
        }

        if (!$logger) {
            return false;
        }

        // Log
        $level   = $this->getExceptionLevel($exception);
        $context = $this->getExceptionContext($exception);
        $message = $exception->getMessage();

        $logger->log($level, $message, $context);

        // Return
        return true;
    }

    protected function dispatchException(Throwable $exception): void {
        try {
            $this->container->make(Dispatcher::class)->dispatch(new ErrorReport($exception));
        } catch (BindingResolutionException) {
            // no action
        }
    }

    protected function getExceptionLevel(Throwable $exception): string {
        $level = LogLevel::ERROR;

        if ($exception instanceof ApplicationException) {
            $level = $exception->getLevel() ?: $level;
        }

        return $level;
    }

    protected function getExceptionLogger(Throwable $exception): ?LoggerInterface {
        $logger = null;

        if ($exception instanceof ApplicationException) {
            $config  = $this->container->make(Repository::class);
            $channel = $exception->getChannel();

            if ($config->get("logging.channels.{$channel}")) {
                $logger = $this->container->make('log')->channel($channel);
            }
        }

        if (!$logger) {
            $logger = $this->getLogger();
        }

        return $logger;
    }

    public function getExceptionMessage(Throwable $exception): string {
        $message = $this->getExceptionTranslatedMessage($exception);
        $code    = $this->getExceptionErrorCode($exception);

        if ($code) {
            $key    = 'errors.message';
            $string = $this->translate($key, [
                'message' => rtrim(trim($message), '.'),
                'code'    => $code,
            ]);

            if ($key !== $string) {
                $message = $string;
            }
        }

        return $message;
    }

    protected function getExceptionTranslatedMessage(Throwable $error): string {
        // Translated?
        if ($error instanceof TranslatedException) {
            return $error->getErrorMessage();
        }

        // Determine key
        $key     = null;
        $default = 'errors.server_error';

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
            $http = [
                401 => 'errors.unauthorized',
                403 => 'errors.forbidden',
                404 => 'errors.not_found',
                419 => 'errors.page_expired',
                429 => 'errors.too_many_requests',
                500 => 'errors.server_error',
                503 => 'errors.service_unavailable',
            ];
            $key  = $http[$error->getStatusCode()]
                ?? "errors.http.{$error->getStatusCode()}";
        } elseif ($error instanceof GraphQLDefinitionException) {
            $key = 'errors.graphql.schema_broken';
        } elseif ($error instanceof GraphQLError) {
            $key = 'errors.graphql.error';
        } else {
            // empty
        }

        // Translate
        $message = null;
        $keys    = [
            $key,
            $error->getMessage()
                ? "errors.messages.{$error->getMessage()}"
                : null,
        ];

        foreach ($keys as $key) {
            $string = $key ? $this->translate($key) : $key;

            if ($key !== $string) {
                $message = $string;
                break;
            }
        }

        if (is_null($message)) {
            $message = $this->translate($default);

            if (reset($keys)) {
                $this->getLogger()?->notice('Missing translation.', [
                    'keys'  => $keys,
                    'error' => $error,
                ]);
            }
        }

        // Return
        return $message;
    }

    protected function getExceptionErrorCode(Throwable $exception): string|int|null {
        $code = null;

        if ($exception instanceof TranslatedException) {
            $code = $exception->getErrorCode();

            if (!$code) {
                $this->getLogger()?->notice('Missing error code.', [
                    'exception' => $exception::class,
                ]);
            }
        } else {
            $code = ErrorCodes::getCode($exception);
        }

        return $code;
    }

    /**
     * @return array<mixed>
     */
    public function getExceptionTrace(Throwable $exception): array {
        $stack  = [];
        $filter = static function (array $trace): array {
            return Arr::except($trace, ['args']);
        };

        do {
            $stack[]   = [
                'class'   => $exception::class,
                'message' => $exception->getMessage(),
                'context' => $this->exceptionContext($exception),
                'code'    => $exception->getCode(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => !($exception instanceof ApplicationMessage)
                    ? (new Collection($exception->getTrace()))->map($filter)->all()
                    : [],
            ];
            $exception = $exception->getPrevious();
        } while ($exception);

        return $stack;
    }

    /**
     * @return array<string,mixed>
     */
    protected function getExceptionContext(Throwable $exception): array {
        return [
            'message' => $this->getExceptionMessage($exception),
            'context' => $this->getExceptionTrace($exception),
        ];
    }

    /**
     * @param array<mixed> $replace
     */
    protected function translate(string $string, array $replace = []): string {
        return $this->getTranslator()?->get($string, $replace) ?? $string;
    }

    protected function getLogger(): ?LoggerInterface {
        try {
            return $this->container->make(LoggerInterface::class);
        } catch (BindingResolutionException) {
            // empty
        }

        return null;
    }

    protected function getTranslator(): ?Translator {
        try {
            return $this->container->make(Translator::class);
        } catch (BindingResolutionException) {
            // empty
        }

        return null;
    }
}
