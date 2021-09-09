<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Events\Subscriber;
use App\Services\Logger\Logger;
use App\Services\Logger\Models\Enums\Category;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Throwable;

abstract class Listener implements Subscriber {
    private bool $disabled = false;

    public function __construct(
        protected Logger $logger,
        protected Repository $config,
        protected ExceptionHandler $exceptionHandler,
    ) {
        // empty
    }

    abstract protected function getCategory(): Category;

    protected function getSafeListener(Closure $closure): Closure {
        return function (mixed ...$args) use ($closure): void {
            if ($this->disabled) {
                return;
            }

            try {
                $closure(...$args);
            } catch (Throwable $exception) {
                $this->disabled = true;

                $this->exceptionHandler->report($exception);
            } finally {
                $this->disabled = false;
            }
        };
    }
}
