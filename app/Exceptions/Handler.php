<?php declare(strict_types = 1);

namespace App\Exceptions;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

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
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

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
        $this->reportable(static function (Throwable $e): void {
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
            $array['stack'] = $this->helper->getTrace($e);
        }

        return $array;
    }
}
