<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;
use Tests\TestCase;

use function iterator_to_array;

/**
 * @internal
 * @coversDefaultClass \App\Utils\Iterators\GroupedIteratorIterator
 */
class GroupedIteratorIteratorTest extends TestCase {
    /**
     * @covers ::getIterator
     * @covers ::setChunkSize
     * @covers ::setOffset
     * @covers ::setLimit
     */
    public function testGetIterator(): void {
        $iterator = new GroupedIteratorIterator(
            new ObjectsIterator(
                Mockery::mock(ExceptionHandler::class),
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 0],
            ),
        );

        $this->assertEquals([[1, 2, 3, 4], [5, 6]], iterator_to_array(
            $iterator->setChunkSize(4)->setOffset(0)->setLimit(6),
        ));
        $this->assertEquals([[1, 2]], iterator_to_array(
            $iterator->setChunkSize(5)->setOffset(0)->setLimit(2),
        ));
        $this->assertEquals([[2, 3, 4], [5, 6, 7], [8, 9, 0]], iterator_to_array(
            $iterator->setChunkSize(3)->setOffset(1)->setLimit(null),
        ));
    }

    /**
     * @covers ::getIterator
     */
    public function testGetIteratorEmpty(): void {
        $this->assertEquals([], iterator_to_array(
            new GroupedIteratorIterator(new ObjectsIterator(
                Mockery::mock(ExceptionHandler::class),
                [],
            )),
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

        $iterator = (new GroupedIteratorIterator(
            new ObjectsIterator(
                Mockery::mock(ExceptionHandler::class),
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 0],
            ),
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
            ->with([1, 2])
            ->once();
        $before
            ->shouldHaveBeenCalled()
            ->with([3])
            ->once();
        $after
            ->shouldHaveBeenCalled()
            ->with([1, 2])
            ->once();
        $after
            ->shouldHaveBeenCalled()
            ->with([3])
            ->once();
    }
}
