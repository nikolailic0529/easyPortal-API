<?php declare(strict_types = 1);

namespace App\Exceptions;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

use function array_merge;

class Handler extends ExceptionHandler implements ContextProvider {
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
        $this->reportable(function (Throwable $exception): bool {
            return !$this->reportException($exception);
        });
    }

    /**
     * @return array<mixed>
     */
    protected function convertExceptionToArray(Throwable $e): array {
        $config = $this->container->make(Repository::class);
        $helper = $this->container->make(Helper::class);
        $array  = [
            'message' => $helper->getMessage($e),
        ];

        if ($config->get('app.debug')) {
            $array['stack'] = $helper->getTrace($e, $this);
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
        $helper  = $this->container->make(Helper::class);
        $level   = $this->getExceptionLevel($exception);
        $message = $exception->getMessage();
        $context = [
            'message' => $helper->getMessage($exception),
            'stack'   => $helper->getTrace($exception, $this),
        ];

        $logger->log($level, $message, $context);

        // Event
        try {
            $this->container->make(Dispatcher::class)->dispatch(new ErrorReport($exception));
        } catch (BindingResolutionException) {
            // no action
        }

        // Return
        return true;
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
            $logger = $this->container->make(LoggerInterface::class);
        }

        return $logger;
    }

    // <editor-fold desc="ContextProvider">
    // =========================================================================
    /**
     * @inheritDoc
     */
    public function getExceptionContext(Throwable $exception): array {
        return $this->exceptionContext($exception);
    }
    // </editor-fold>
}
