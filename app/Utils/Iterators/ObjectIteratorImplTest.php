<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use Exception;
use stdClass;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Utils\Iterators\ObjectIteratorImpl
 */
class ObjectIteratorImplTest extends TestCase {
    /**
     * @covers ::chunkLoaded
     * @covers ::onBeforeChunk
     */
    public function testChunkLoaded(): void {
        $iterator = new class() extends ObjectIteratorImpl {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritDoc
             */
            protected function getChunkVariables(int $limit): array {
                return [];
            }

            /**
             * @inheritDoc
             */
            public function chunkLoaded(array $items): void {
                parent::chunkLoaded($items);
            }
        };

        // Empty chunk
        $iterator->chunkLoaded([]);

        // Non-empty chunk
        $exception = new Exception(__METHOD__);

        $this->expectExceptionObject($exception);

        $iterator->onBeforeChunk(static function () use ($exception): void {
            throw $exception;
        });

        $iterator->chunkLoaded([new stdClass()]);
    }

    /**
     * @covers ::chunkProcessed
     * @covers ::onAfterChunk
     */
    public function testChunkProcessed(): void {
        $iterator = new class() extends ObjectIteratorImpl {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritDoc
             */
            protected function getChunkVariables(int $limit): array {
                return [];
            }

            /**
             * @inheritDoc
             */
            public function chunkProcessed(array $items): bool {
                return parent::chunkProcessed($items);
            }
        };

        // Empty chunk
        $iterator->chunkProcessed([]);

        // Non-empty chunk
        $exception = new Exception(__METHOD__);

        $this->expectExceptionObject($exception);

        $iterator->onAfterChunk(static function () use ($exception): void {
            throw $exception;
        });
        $iterator->chunkProcessed([new stdClass()]);
    }

    /**
     * @covers ::chunkPrepare
     */
    public function testChunkPrepareInfiniteLoopDetection(): void {
        $iterator = new class() extends ObjectIteratorImpl {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                $this->retriever = null;
            }

            /**
             * @inheritDoc
             */
            protected function getChunkVariables(int $limit): array {
                return [];
            }

            /**
             * @inheritDoc
             */
            public function chunkPrepare(array $items): array {
                return parent::chunkPrepare($items);
            }
        };

        $chunk = [['a' => 'a'], ['b' => 'b'], ['c' => 'c']];

        $this->assertEquals($chunk, $iterator->chunkPrepare($chunk));
        $this->assertEquals([], $iterator->chunkPrepare($chunk));
    }
}
