<?php declare(strict_types = 1);

namespace App\Utils\Console;

use Closure;
use Illuminate\Console\Command;

use function sleep;
use function time;

/**
 * @mixin Command
 */
trait WithWait {
    /**
     * @template T
     *
     * @param Closure(): T $closure
     *
     * @return T
     */
    protected function wait(int $timeout, Closure $closure): mixed {
        $result  = $closure();
        $started = time();

        while ($result === false && $this->waiting($started, $timeout)) {
            $result = $closure();

            $this->sleep();
        }

        return $result;
    }

    protected function waiting(int $started, int $timeout): bool {
        return (time() - $started) <= $timeout;
    }

    protected function sleep(): void {
        if (!$this->getLaravel()->runningUnitTests()) {
            sleep(1);
        }
    }
}
