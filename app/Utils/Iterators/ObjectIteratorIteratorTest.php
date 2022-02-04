<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use Closure;
use Mockery;
use Tests\TestCase;

use function iterator_to_array;

/**
 * @internal
 * @coversDefaultClass \App\Utils\Iterators\ObjectIteratorIterator
 */
class ObjectIteratorIteratorTest extends TestCase {
    /**
     * @covers ::getIterator
     * @covers ::setOffset
     * @covers ::setLimit
     */
    public function testGetIterator(): void {
        $iterator = new ObjectIteratorIterator(
            new OneChunkOffsetBasedObjectIterator(static function (): array {
                return [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];
            }),
            static function (int $item): int {
                return $item * 10;
            },
        );

        $this->assertEquals([10, 20, 30, 40, 50, 60, 70, 80, 90, 0], iterator_to_array($iterator));
        $this->assertEquals([30, 40, 50, 60, 70, 80], iterator_to_array(
            $iterator->setOffset(2)->setLimit(6),
        ));
        $this->assertEquals([60, 70, 80, 90, 0], iterator_to_array(
            $iterator->setOffset(5)->setLimit(null),
        ));
    }

    /**
     * @covers ::onInit
     * @covers ::onFinish
     * @covers ::onBeforeChunk
     * @covers ::onAfterChunk
     */
    public function testEvents(): void {
        $init   = Mockery::spy(static function (): void {
            // empty
        });
        $finish = Mockery::spy(static function (): void {
            // empty
        });
        $before = Mockery::spy(static function (): void {
            // empty
        });
        $after  = Mockery::spy(static function (): void {
            // empty
        });

        $iterator = (new ObjectIteratorIterator(
            new OneChunkOffsetBasedObjectIterator(static function (): array {
                return [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];
            }),
            static function (int $item): int {
                return $item * 10;
            },
        ))
            ->onInit(Closure::fromCallable($init))
            ->onFinish(Closure::fromCallable($finish))
            ->onBeforeChunk(Closure::fromCallable($before))
            ->onAfterChunk(Closure::fromCallable($after))
            ->setChunkSize(2)
            ->setLimit(3);

        iterator_to_array($iterator);

        $init
            ->shouldHaveBeenCalled()
            ->once();
        $finish
            ->shouldHaveBeenCalled()
            ->once();
        $before
            ->shouldHaveBeenCalled()
            ->with([10, 20])
            ->once();
        $before
            ->shouldHaveBeenCalled()
            ->with([30])
            ->once();
        $after
            ->shouldHaveBeenCalled()
            ->with([10, 20])
            ->once();
        $after
            ->shouldHaveBeenCalled()
            ->with([30])
            ->once();
    }
}
