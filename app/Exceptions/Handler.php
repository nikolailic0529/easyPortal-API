<?php declare(strict_types = 1);

namespace App\Exceptions;

use App\Exceptions\Contracts\ApplicationMessage;
use App\Exceptions\Contracts\ExternalException;
use App\Exceptions\Contracts\GenericException;
use App\Exceptions\Contracts\TranslatedException;
use App\Exceptions\Exceptions\FailedToSendMail;
use App\Services\Keycloak\Exceptions\Auth\AuthorizationFailed;
use App\Services\Keycloak\Exceptions\Auth\InvalidCredentials;
use App\Services\Keycloak\Exceptions\Auth\InvalidIdentity;
use App\Services\Keycloak\Exceptions\Auth\StateMismatch;
use App\Services\Keycloak\Exceptions\Auth\UnknownScope;
use App\Services\Keycloak\Exceptions\Auth\UserDisabled;
use App\Services\Service;
use Elastic\Adapter\Exceptions\BulkOperationException;
use Exception;
use GraphQL\Error\Error as GraphQLError;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Client\RequestException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Nuwave\Lighthouse\Exceptions\DefinitionException;
use Nuwave\Lighthouse\Exceptions\RateLimitException;
use Nuwave\Lighthouse\Exceptions\RendersErrorsExtensions;
use Nuwave\Lighthouse\Exceptions\ValidationException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface as MailerException;
use Throwable;

use function array_merge;
use function array_slice;
use function count;
use function is_null;
use function reset;
use function response;
use function rtrim;
use function str_starts_with;
use function trim;

class Handler extends ExceptionHandler {
    /**
     * A list of the exception types that are not reported.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        AuthorizationFailed::class,
        InvalidCredentials::class,
        InvalidIdentity::class,
        StateMismatch::class,
        UnknownScope::class,
        UserDisabled::class,
        DefinitionException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void {
        $this->map(GraphQLError::class, function (GraphQLError $exception): Throwable {
            if ($exception->getPrevious()) {
                $exception = $this->mapException($exception->getPrevious());
            }

            return $exception;
        });
        $this->map(MailerException::class, static function (MailerException $exception): Throwable {
            return new FailedToSendMail($exception);
        });

        $this->reportable(function (Throwable $exception): void {
            $this->dispatchException($exception);
        });
        $this->reportable(function (Throwable $exception): bool {
            return !$this->reportException($exception);
        });
    }

    public function mapException(Throwable $e): Throwable {
        return parent::mapException($e);
    }

    protected function unauthenticated(mixed $request, AuthenticationException $exception): Response {
        // By default, Laravel will redirect the User to the "login" page, but
        // this is unwanted behavior in our case.
        return $this->shouldReturnJson($request, $exception)
            ? response()->json(['message' => $exception->getMessage()], 401)
            : $this->prepareResponse($request, new HttpException(401, $exception->getMessage(), $exception));
    }

    /**
     * @inheritDoc
     */
    protected function prepareResponse($request, Throwable $e) {
        // Some errors may happen at a very early stage when we cannot render
        // them yet, in this case, we will return JSON.
        try {
            return parent::prepareResponse($request, $e);
        } catch (Exception) {
            return $this->prepareJsonResponse($request, $e);
        }
    }

    /**
     * @return array<mixed>
     */
    protected function convertExceptionToArray(Throwable $e): array {
        $config = $this->container->make(Repository::class);
        $array  = $this->getExceptionData($e);

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

        if ($e instanceof GraphQLError) {
            // Variables not available :(
            // https://github.com/webonyx/graphql-php/issues/980
            $context = array_merge($context, [
                'graphql' => [
                    'query' => $e->getSource()?->body,
                ],
            ]);
        }

        if ($e instanceof RendersErrorsExtensions) {
            $context = array_merge($context, $e->extensionsContent());
        }

        if ($e instanceof BulkOperationException) {
            $context['elastic'] = $e->rawResult();
        }

        if ($e instanceof ApplicationException) {
            $context = array_merge($context, $e->getContext());
        } elseif ($e instanceof RequestException) {
            $expectedType = 'application/json';
            $contentType  = $e->response->header('Content-Type');

            if ($contentType === $expectedType || str_starts_with($contentType, "{$expectedType};")) {
                $context = array_merge($context, [
                    'json' => $e->response->json(),
                ]);
            }
        } else {
            // empty
        }

        return $context;
    }

    protected function reportException(Throwable $exception): bool {
        // Get logger (channel)
        $logger = $this->getLogger($exception);

        if (!$logger) {
            return false;
        }

        // Log
        $level   = $this->getExceptionLevel($exception);
        $context = $this->getExceptionData($exception);
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
        if ($error instanceof TranslatedException && $this->getTranslator()) {
            return $error->getErrorMessage();
        }

        // Determine key
        $string = null;

        if ($error instanceof TokenMismatchException) {
            $string = 'errors.page_expired';
        } elseif ($error instanceof AuthenticationException) {
            $string = 'errors.unauthenticated';
        } elseif ($error instanceof AuthorizationException) {
            $string = 'errors.unauthorized';
        } elseif ($error instanceof RateLimitException) {
            $string = 'errors.too_many_requests';
        } elseif ($error instanceof SuspiciousOperationException) {
            $string = 'errors.not_found';
        } elseif ($error instanceof ValidationException) {
            $string = 'errors.validation_failed';
        } elseif ($error instanceof RecordsNotFoundException) {
            $string = 'errors.not_found';
        } elseif ($error instanceof HttpExceptionInterface) {
            $http   = [
                401 => 'errors.unauthorized',
                403 => 'errors.forbidden',
                404 => 'errors.not_found',
                419 => 'errors.page_expired',
                429 => 'errors.too_many_requests',
                500 => 'errors.server_error',
                503 => 'errors.service_unavailable',
            ];
            $string = $http[$error->getStatusCode()]
                ?? "errors.http.{$error->getStatusCode()}";
        } elseif ($error instanceof DefinitionException) {
            $string = 'errors.graphql.schema_broken';
        } elseif ($error instanceof GraphQLError) {
            $string = 'errors.graphql.error';
        } else {
            // empty
        }

        // Translate
        $message = null;
        $keys    = [
            $string,
            $error->getMessage()
                ? "errors.messages.{$error->getMessage()}"
                : null,
        ];

        foreach ($keys as $key) {
            $translated = $key ? $this->translate($key) : $key;

            if ($key !== $translated) {
                $message = $translated;
                break;
            }
        }

        if (is_null($message)) {
            $default = 'errors.server_error';
            $message = $this->translate($default);

            if ($message === $default) {
                $config = $this->container->make(Repository::class);

                if ($config->get('app.debug')) {
                    $message = $error->getMessage();
                } else {
                    $message = 'Internal Server Error.';
                }
            }

            if (reset($keys) && $this->getTranslator()) {
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
    protected function getExceptionStacktrace(Throwable $exception): array {
        $stack    = [];
        $filter   = static function (array $trace): array {
            return Arr::except($trace, ['args']);
        };
        $previous = [];

        do {
            $fullTrace = (new Collection($exception->getTrace()))->map($filter)->all();
            $trace     = $fullTrace;

            if ($exception instanceof ApplicationMessage) {
                $trace = [];
            } elseif ($previous) {
                $remove = null;

                for ($i = count($fullTrace) - 1, $j = count($previous) - 1; $i >= 0 && $j >= 0; $i--, $j--) {
                    if (isset($previous[$j]) && $fullTrace[$i] === $previous[$j]) {
                        $remove = $i;
                    } else {
                        break;
                    }
                }

                if ($remove !== null) {
                    $trace = array_slice($fullTrace, 0, $remove + 1);
                }
            }

            $stack[]   = [
                'class'   => $exception::class,
                'message' => $exception->getMessage(),
                'code'    => $exception->getCode(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $trace,
            ];
            $previous  = $fullTrace;
            $exception = $exception->getPrevious();
        } while ($exception);

        return $stack;
    }

    /**
     * @return array<mixed>
     */
    protected function getExceptionContext(Throwable $exception): array {
        $context = [];

        do {
            $data = $this->exceptionContext($exception);

            if ($data) {
                $context[] = [
                    'class'   => $exception::class,
                    'message' => $exception->getMessage(),
                    'context' => $data,
                    'level'   => $this->getExceptionLevel($exception),
                ];
            }

            $exception = $exception->getPrevious();
        } while ($exception);

        return $context;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getExceptionTags(Throwable $exception): array {
        // Prepare
        $tags = [];

        // Code
        $code = $this->getExceptionErrorCode($exception);

        if ($code) {
            $tags['code'] = $code;
        }

        // External
        if ($exception instanceof ExternalException) {
            $tags['external'] = Service::getServiceName($exception) ?? 'unknown';
        }

        // Return
        return $tags;
    }

    /**
     * @return array<string>
     */
    protected function getExceptionFingerprint(Throwable $exception): array {
        $fingerprint = [
            $exception::class,
            $exception->getMessage(),
        ];

        if ($exception instanceof GenericException) {
            $previous = $exception->getPrevious();

            if ($previous) {
                $fingerprint = array_merge($fingerprint, $this->getExceptionFingerprint($previous));
            }
        }

        return $fingerprint;
    }

    /**
     * @return array<string,mixed>
     */
    public function getExceptionData(Throwable $exception): array {
        return [
            'message'     => $this->getExceptionMessage($exception),
            'tags'        => $this->getExceptionTags($exception),
            'context'     => $this->getExceptionContext($exception),
            'stacktrace'  => $this->getExceptionStacktrace($exception),
            'fingerprint' => $this->getExceptionFingerprint($exception),
        ];
    }

    /**
     * @param array<mixed> $replace
     */
    protected function translate(string $string, array $replace = []): string {
        return $this->getTranslator()?->get($string, $replace) ?? $string;
    }

    protected function getLogger(Throwable $exception = null): ?LoggerInterface {
        $logger = null;

        try {
            if ($exception instanceof ApplicationException) {
                $config  = $this->container->make(Repository::class);
                $channel = $exception->getChannel();

                if ($config->get("logging.channels.{$channel}")) {
                    $logger = $this->container->make('log')->channel($channel);
                }
            }

            if (!$logger) {
                $logger = $this->container->make(LoggerInterface::class);
            }
        } catch (BindingResolutionException) {
            $logger = null;
        }

        return $logger;
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
