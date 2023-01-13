<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use App\Utils\Iterators\Exceptions\InfiniteLoopDetected;
use Closure;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

use function array_slice;
use function iterator_to_array;
use function range;

/**
 * @internal
 * @covers \App\Utils\Iterators\OffsetBasedObjectIterator
 */
class OffsetBasedObjectIteratorTest extends TestCase {
    public function testGetIterator(): void {
        $data     = range(1, 10);
        $onInit   = Mockery::spy(static function (): void {
            // empty
        });
        $onFinish = Mockery::spy(static function (): void {
            // empty
        });
        $executor = Mockery::spy(static function (array $variables = []) use ($data): array {
            return array_slice($data, $variables['offset'] ?? 0, $variables['limit']);
        });
        $iterator = (new OffsetBasedObjectIterator(
            Closure::fromCallable($executor),
        ))
            ->setChunkSize(5)
            ->onInit(Closure::fromCallable($onInit))
            ->onFinish(Closure::fromCallable($onFinish));

        $expected = $data;
        $actual   = iterator_to_array($iterator);

        self::assertEquals($expected, $actual);

        $onInit->shouldHaveBeenCalled()->once();
        $onFinish->shouldHaveBeenCalled()->once();
        $executor->shouldHaveBeenCalled()->times(3);
    }

    public function testGetIteratorWithLimitOffset(): void {
        $data     = range(1, 10);
        $executor = Mockery::spy(static function (array $variables = []) use ($data): array {
            return array_slice($data, $variables['offset'] ?? 0, $variables['limit']);
        });

        $onBeforeChunk = Mockery::spy(static function (): void {
            // empty
        });
        $onAfterChunk  = Mockery::spy(static function (): void {
            // empty
        });
        $iterator      = (new OffsetBasedObjectIterator(
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
        $executor = Mockery::spy(static function (array $variables = []) use ($data): array {
            self::assertEquals(2, $variables['limit']);

            return array_slice($data, $variables['offset'] ?? 0, $variables['limit']);
        });

        $expected = $data;
        $iterator = (new OffsetBasedObjectIterator(
            Closure::fromCallable($executor),
        ))
            ->setChunkSize(2)
            ->setLimit(10);
        $actual   = iterator_to_array($iterator);

        self::assertEquals($expected, $actual);

        $executor->shouldHaveBeenCalled()->times(5);
    }

    public function testGetIteratorChunkGreaterThanLimit(): void {
        $data     = range(1, 10);
        $executor = Mockery::spy(static function (array $variables = []) use ($data): array {
            self::assertEquals(2, $variables['limit']);

            return array_slice($data, $variables['offset'] ?? 0, $variables['limit']);
        });

        $expected = [1, 2];
        $iterator = (new OffsetBasedObjectIterator(
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
        $iterator = (new OffsetBasedObjectIterator(
            Closure::fromCallable($executor),
        ))
            ->setLimit(0);
        $actual   = iterator_to_array($iterator);

        self::assertEquals($expected, $actual);

        $executor->shouldNotHaveBeenCalled();
    }

    public function testGetIteratorInfiniteLoop(): void {
        $data     = range(1, 10);
        $executor = Mockery::spy(static function () use ($data): array {
            return $data;
        });

        $iterator = (new OffsetBasedObjectIterator(
            Closure::fromCallable($executor),
        ))
            ->setChunkSize(5);

        self::expectException(InfiniteLoopDetected::class);

        iterator_to_array($iterator);
    }

    public function testSetOffset(): void {
        $iterator = new OffsetBasedObjectIterator(static function (): array {
            return [];
        });

        self::assertEquals(123, $iterator->setOffset('123')->getOffset());
        self::assertEquals(321, $iterator->setOffset(321)->getOffset());
    }

    public function testSetOffsetInvalidType(): void {
        self::expectException(InvalidArgumentException::class);

        $iterator = new OffsetBasedObjectIterator(static function (): array {
            return [];
        });

        $iterator->setOffset('invalid');
    }
}
