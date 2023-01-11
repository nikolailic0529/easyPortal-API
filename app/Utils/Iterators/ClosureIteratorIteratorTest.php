<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use App\Utils\Iterators\Contracts\IteratorFatalError;
use App\Utils\Iterators\Exceptions\BrokenIteratorDetected;
use Closure;
use Error;
use Exception;
use Illuminate\Support\Arr;
use Mockery;
use Tests\TestCase;

use function count;
use function is_int;
use function iterator_to_array;

/**
 * @internal
 * @covers \App\Utils\Iterators\ClosureIteratorIterator
 */
class ClosureIteratorIteratorTest extends TestCase {
    public function testGetIterator(): void {
        $iterator = new ClosureIteratorIterator(
            new ObjectsIterator([1, 2, 3, 4, 5, 6, 7, 8, 9, 0]),
            static function (mixed $item): mixed {
                if ($item === 5) {
                    $item = null;
                }

                if (is_int($item)) {
                    $item = $item * 10;
                }

                return $item;
            },
        );

        self::assertEquals(
            [
                0 => 10,
                1 => 20,
                2 => 30,
                3 => 40,
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
            static function (mixed $item): mixed {
                if ($item === 4) {
                    throw new Exception('test');
                }

                if (is_int($item)) {
                    $item = $item * 10;
                }

                return $item;
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

    public function testChunkConvertError(): void {
        $items    = [1, 2, 3, 4, 5];
        $error    = new Exception();
        $convert  = static function (mixed $item) use ($error): mixed {
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
            Arr::except($items, 2),
            $iterator->chunkConvert($items),
        );
    }

    public function testChunkConvertFatalError(): void {
        $items    = [1, 2, 3, 4, 5];
        $error    = new class() extends Exception implements IteratorFatalError {
            // empty
        };
        $convert  = static function (mixed $item) use ($error): mixed {
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

    public function testChunkConvertBrokenIteratorDetectionChunkFull(): void {
        $items    = [1, 2, 3, 4, 5];
        $error    = new Error();
        $convert  = static function () use ($error): int {
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

        $iterator->setChunkSize(count($items))->chunkConvert($items);
    }

    public function testChunkConvertBrokenIteratorDetectionChunkPart(): void {
        $items    = [1, 2, 3, 4, 5];
        $error    = new Error();
        $convert  = static function () use ($error): int {
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

        self::assertCount(0, $iterator->chunkConvert($items));
    }
}
