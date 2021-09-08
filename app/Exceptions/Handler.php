<?php declare(strict_types = 1);

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
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

    public function __construct(
        Container $container,
        protected Repository $config,
        protected Helper $helper,
    ) {
        parent::__construct($container);
    }

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
        $array = [
            'message' => $this->helper->getMessage($e),
        ];

        if ($this->config->get('app.debug')) {
            $array['stack'] = $this->helper->getTrace($e, $this);
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
            $logger = $exception instanceof ApplicationException
                ? $this->container->make('log')->channel($exception->getChannel())
                : $this->container->make(LoggerInterface::class);
        } catch (BindingResolutionException) {
            // no action
        }

        if (!$logger) {
            return false;
        }

        // Log
        $level   = $exception instanceof ApplicationException
            ? ($exception->getLevel() ?: LogLevel::ERROR)
            : LogLevel::ERROR;
        $message = $exception->getMessage();
        $context = [
            'message' => $this->helper->getMessage($exception),
            'stack'   => $this->helper->getTrace($exception, $this),
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
