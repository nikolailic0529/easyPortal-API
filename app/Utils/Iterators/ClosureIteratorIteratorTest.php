<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use App\Utils\Iterators\Contracts\IteratorFatalError;
use App\Utils\Iterators\Exceptions\BrokenIteratorDetected;
use Closure;
use Error;
use Exception;
use Mockery;
use Tests\TestCase;

use function iterator_to_array;

/**
 * @internal
 * @coversDefaultClass \App\Utils\Iterators\ClosureIteratorIterator
 */
class ClosureIteratorIteratorTest extends TestCase {
    /**
     * @covers ::getIterator
     * @covers ::setOffset
     * @covers ::setLimit
     * @covers ::chunkConvert
     */
    public function testGetIterator(): void {
        $iterator = new ClosureIteratorIterator(
            new ObjectsIterator([1, 2, 3, 4, 5, 6, 7, 8, 9, 0]),
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
     * @covers ::onError
     * @covers ::onFinish
     * @covers ::onBeforeChunk
     * @covers ::onAfterChunk
     * @covers ::chunkConvert
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
        $error  = Mockery::spy(static function (): void {
            // empty
        });

        $iterator = (new ClosureIteratorIterator(
            new ObjectsIterator([1, 2, 3, 4, 5, 6, 7, 8, 9, 0]),
            static function (int $item): int {
                if ($item === 4) {
                    throw new Exception('test');
                }

                return $item * 10;
            },
        ))
            ->onInit(Closure::fromCallable($init))
            ->onError(Closure::fromCallable($error))
            ->onFinish(Closure::fromCallable($finish))
            ->onBeforeChunk(Closure::fromCallable($before))
            ->onAfterChunk(Closure::fromCallable($after))
            ->setChunkSize(2)
            ->setLimit(4);

        iterator_to_array($iterator);

        $init
            ->shouldHaveBeenCalled()
            ->once();
        $error
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
                3 => null,
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
                3 => null,
            ])
            ->once();
    }

    /**
     * @covers ::chunkConvert
     */
    public function testChunkConvertError(): void {
        $items    = [1, 2, 3, 4, 5];
        $error    = new Exception();
        $convert  = static function (int $item) use ($error): int {
            if ($item === 3) {
                throw $error;
            }

            return $item;
        };
        $iterator = new class(new ObjectsIterator($items), $convert) extends ClosureIteratorIterator {
            /**
             * @inheritDoc
             */
            public function chunkConvert(array $items): array {
                return parent::chunkConvert($items);
            }
        };

        self::assertEquals(
            [1, 2, null, 4, 5],
            $iterator->chunkConvert($items),
        );
    }

    /**
     * @covers ::chunkConvert
     */
    public function testChunkConvertFatalError(): void {
        $items    = [1, 2, 3, 4, 5];
        $error    = new class() extends Exception implements IteratorFatalError {
            // empty
        };
        $convert  = static function (int $item) use ($error): int {
            if ($item === 3) {
                throw $error;
            }

            return $item;
        };
        $iterator = new class(new ObjectsIterator($items), $convert) extends ClosureIteratorIterator {
            /**
             * @inheritDoc
             */
            public function chunkConvert(array $items): array {
                return parent::chunkConvert($items);
            }
        };

        self::expectExceptionObject($error);

        $iterator->chunkConvert($items);
    }

    /**
     * @covers ::chunkConvert
     */
    public function testChunkConvertBrokenIteratorDetection(): void {
        $items    = [1, 2, 3, 4, 5];
        $error    = new Error();
        $convert  = static function (int $item) use ($error): int {
            throw $error;
        };
        $iterator = new class(new ObjectsIterator($items), $convert) extends ClosureIteratorIterator {
            /**
             * @inheritDoc
             */
            public function chunkConvert(array $items): array {
                return parent::chunkConvert($items);
            }
        };

        self::expectExceptionObject(new BrokenIteratorDetected($iterator::class));

        $iterator->chunkConvert($items);
    }
}
