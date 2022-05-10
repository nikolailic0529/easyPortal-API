<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
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
        $handler  = Mockery::mock(ExceptionHandler::class);
        $iterator = new ObjectIteratorIterator(
            $handler,
            new ObjectsIterator($handler, [1, 2, 3, 4, 5, 6, 7, 8, 9, 0]),
            static function (int $item): ?int {
                return $item !== 5 ? $item * 10 : null;
            },
        );

        self::assertEquals(
            [
                0 => 10,
                1 => 20,
                2 => 30,
                3 => 40,
                4 => null,
                5 => 60,
                6 => 70,
                7 => 80,
                8 => 90,
                9 => 0,
            ],
            iterator_to_array($iterator),
        );
        self::assertEquals(
            [
                0 => 30,
                1 => 40,
                2 => null,
                3 => 60,
                4 => 70,
                5 => 80,
            ],
            iterator_to_array(
                $iterator->setOffset(2)->setLimit(6),
            ),
        );
        self::assertEquals([60, 70, 80, 90, 0], iterator_to_array(
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

        $handler  = Mockery::mock(ExceptionHandler::class);
        $iterator = (new ObjectIteratorIterator(
            $handler,
            new ObjectsIterator($handler, [1, 2, 3, 4, 5, 6, 7, 8, 9, 0]),
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
            ->with([
                0 => 10,
                1 => 20,
            ])
            ->once();
        $before
            ->shouldHaveBeenCalled()
            ->with([
                2 => 30,
            ])
            ->once();
        $after
            ->shouldHaveBeenCalled()
            ->with([
                0 => 10,
                1 => 20,
            ])
            ->once();
        $after
            ->shouldHaveBeenCalled()
            ->with([
                2 => 30,
            ])
            ->once();
    }
}
