<?php declare(strict_types = 1);

namespace App\Exceptions;

use Exception;
use Tests\TestCase;
use Throwable;

/**
 * @internal
 * @coversDefaultClass \App\Exceptions\Handler
 */
class HandlerTest extends TestCase {
    /**
     * @covers ::exceptionContext
     */
    public function testExceptionContext(): void {
        $exception = new class() extends Exception implements Contextable {
            /**
             * @inheritDoc
             */
            public function context(): array {
                return [1, 2, 3];
            }
        };
        $handler   = new class() extends Handler {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function exceptionContext(Throwable $e): array {
                return parent::exceptionContext($e);
            }
        };

        $this->assertEquals([1, 2, 3], $handler->exceptionContext($exception));
    }
}
