<?php declare(strict_types = 1);

namespace Tests;

use ErrorException;

use function in_array;
use function set_error_handler;

use const E_DEPRECATED;
use const E_USER_DEPRECATED;

/**
 * Similar to {@see \Illuminate\Foundation\Testing\Concerns\InteractsWithDeprecationHandling}
 * but will also call original handler.
 *
 * @mixin TestCase
 */
trait WithDeprecations {
    /**
     * @var callable(int, string, string, int): bool|null
     */
    private mixed $withDeprecations = null;

    /**
     * @before
     */
    public function initWithDeprecations(): void {
        $this->afterApplicationCreated(function (): void {
            $this->withDeprecations = set_error_handler(
                function (int $level, string $message, string $file = '', int $line = 0): bool {
                    if ($this->withDeprecations) {
                        ($this->withDeprecations)($level, $message, $file, $line);
                    }

                    if (in_array($level, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
                        throw new ErrorException($message, 0, $level, $file, $line);
                    }

                    return true;
                },
            );
        });

        $this->beforeApplicationDestroyed(function (): void {
            if ($this->withDeprecations) {
                set_error_handler($this->withDeprecations);
                $this->withDeprecations = null;
            }
        });
    }
}
