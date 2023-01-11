<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use Closure;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

use function iterator_to_array;
use function range;

/**
 * @internal
 * @covers \App\Utils\Iterators\OneChunkOffsetBasedObjectIterator
 */
class OneChunkOffsetBasedObjectIteratorTest extends TestCase {
    public function testGetIterator(): void {
        $data     = range(1, 10);
        $onInit   = Mockery::spy(static function (): void {
            // empty
        });
        $onFinish = Mockery::spy(static function (): void {
            // empty
        });
        $executor = Mockery::spy(static function () use ($data): array {
            return $data;
        });
        $iterator = (new OneChunkOffsetBasedObjectIterator(
            Closure::fromCallable($executor),
        ))
            ->setChunkSize(5)
            ->onInit(Closure::fromCallable($onInit))
            ->onFinish(Closure::fromCallable($onFinish));

        $expected = $data;
        $actual   = iterator_to_array($iterator);
        $second   = iterator_to_array($iterator);

        self::assertEquals($expected, $actual);
        self::assertEquals($second, $actual);

        $onInit->shouldHaveBeenCalled()->twice();
        $onFinish->shouldHaveBeenCalled()->twice();
        $executor->shouldHaveBeenCalled()->times(1);
    }

    public function testGetIteratorWithLimitOffset(): void {
        $data     = range(1, 10);
        $executor = Mockery::spy(static function () use ($data): array {
            return $data;
        });

        $onBeforeChunk = Mockery::spy(static function (): void {
            // empty
        });
        $onAfterChunk  = Mockery::spy(static function (): void {
            // empty
        });
        $iterator      = (new OneChunkOffsetBasedObjectIterator(
            Closure::fromCallable($executor),
        ))
            ->onBeforeChunk(Closure::fromCallable($onBeforeChunk))
            ->onAfterChunk(Closure::fromCallable($onAfterChunk))
            ->setOffset(5)
            ->setLimit(2)
            ->setChunkSize(5);

        $expected = [6, 7];
        $actual   = iterator_to_array($iterator);

        self::assertEquals($expected, $actual);

        $onBeforeChunk->shouldHaveBeenCalled();
        $onAfterChunk->shouldHaveBeenCalled();
        $executor->shouldHaveBeenCalled()->times(1);
    }

    public function testGetIteratorChunkLessThanLimit(): void {
        $data     = range(1, 10);
        $executor = Mockery::spy(static function () use ($data): array {
            return $data;
        });

        $expected = $data;
        $iterator = (new OneChunkOffsetBasedObjectIterator(
            Closure::fromCallable($executor),
        ))
            ->setChunkSize(2)
            ->setLimit(10);
        $actual   = iterator_to_array($iterator);

        self::assertEquals($expected, $actual);

        $executor->shouldHaveBeenCalled()->times(1);
    }

    public function testGetIteratorChunkGreaterThanLimit(): void {
        $data     = range(1, 10);
        $executor = Mockery::spy(static function () use ($data): array {
            return $data;
        });

        $expected = [1, 2];
        $iterator = (new OneChunkOffsetBasedObjectIterator(
            Closure::fromCallable($executor),
        ))
            ->setChunkSize(50)
            ->setLimit(2);
        $actual   = iterator_to_array($iterator);

        self::assertEquals($expected, $actual);

        $executor->shouldHaveBeenCalled()->times(1);
    }

    public function testGetIteratorLimitZero(): void {
        $executor = Mockery::spy(static function (): array {
            return [];
        });

        $expected = [];
        $iterator = (new OneChunkOffsetBasedObjectIterator(
            Closure::fromCallable($executor),
        ))->setLimit(0);
        $actual   = iterator_to_array($iterator);

        self::assertEquals($expected, $actual);

        $executor->shouldNotHaveBeenCalled();
    }

    public function testSetOffset(): void {
        $iterator = new OneChunkOffsetBasedObjectIterator(static function (): array {
            return [];
        });

        self::assertEquals(123, $iterator->setOffset('123')->getOffset());
        self::assertEquals(321, $iterator->setOffset(321)->getOffset());
    }

    public function testSetOffsetInvalidType(): void {
        self::expectException(InvalidArgumentException::class);

        $iterator = new OneChunkOffsetBasedObjectIterator(static function (): array {
            return [];
        });

        $iterator->setOffset('invalid');
    }
}
