<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Events\Subscriber;
use App\Services\Logger\Logger;
use App\Services\Logger\Models\Enums\Category;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Psr\Log\LoggerInterface;
use Throwable;

abstract class Listener implements Subscriber {
    private bool $disabled = false;

    public function __construct(
        protected Logger $logger,
        protected Repository $config,
        protected LoggerInterface $log,
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

                $this->log->error($exception->getMessage(), [
                    'exception' => $exception,
                ]);
            } finally {
                $this->disabled = false;
            }
        };
    }
}
