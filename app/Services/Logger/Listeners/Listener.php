<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\Logger\Logger;
use App\Services\Logger\Models\Enums\Category;
use App\Utils\Providers\EventsProvider;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Throwable;

abstract class Listener implements EventsProvider {
    private bool $disabled = false;

    public function __construct(
        protected Logger $logger,
        protected ExceptionHandler $exceptionHandler,
    ) {
        // empty
    }

    abstract protected function getCategory(): Category;

    /**
     * @param Closure(): void $closure
     */
    protected function call(Closure $closure): void {
        if ($this->disabled) {
            return;
        }

        try {
            $closure();
        } catch (Throwable $exception) {
            $this->disabled = true;

            $this->exceptionHandler->report($exception);
        } finally {
            $this->disabled = false;
        }
    }
}
