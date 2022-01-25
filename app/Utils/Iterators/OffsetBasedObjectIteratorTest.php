<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use Closure;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

use function array_slice;
use function iterator_to_array;
use function range;

/**
 * @internal
 * @coversDefaultClass \App\Utils\Iterators\OffsetBasedObjectIterator
 */
class OffsetBasedObjectIteratorTest extends TestCase {
    /**
     * @covers ::getIterator
     */
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
        $iterator = (new OffsetBasedObjectIterator(Closure::fromCallable($executor)))
            ->setChunkSize(5)
            ->onInit(Closure::fromCallable($onInit))
            ->onFinish(Closure::fromCallable($onFinish));

        $expected = $data;
        $actual   = iterator_to_array($iterator);

        $this->assertEquals($expected, $actual);

        $onInit->shouldHaveBeenCalled()->once();
        $onFinish->shouldHaveBeenCalled()->once();
        $executor->shouldHaveBeenCalled()->times(3);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorWithLimitOffset(): void {
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
        $iterator      = (new OffsetBasedObjectIterator(Closure::fromCallable($executor)))
            ->onBeforeChunk(Closure::fromCallable($onBeforeChunk))
            ->onAfterChunk(Closure::fromCallable($onAfterChunk))
            ->setOffset(5)
            ->setLimit(2)
            ->setChunkSize(5);

        $expected = [6, 7];
        $actual   = iterator_to_array($iterator);

        $this->assertEquals($expected, $actual);

        $onBeforeChunk->shouldHaveBeenCalled();
        $onAfterChunk->shouldHaveBeenCalled();
        $executor->shouldHaveBeenCalled()->times(1);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorChunkLessThanLimit(): void {
        $data     = range(1, 10);
        $executor = Mockery::spy(function (array $variables = []) use ($data): array {
            $this->assertEquals(2, $variables['limit']);

            return array_slice($data, $variables['offset'] ?? 0, $variables['limit']);
        });

        $expected = $data;
        $iterator = (new OffsetBasedObjectIterator(Closure::fromCallable($executor)))
            ->setChunkSize(2)
            ->setLimit(10);
        $actual   = iterator_to_array($iterator);

        $this->assertEquals($expected, $actual);

        $executor->shouldHaveBeenCalled()->times(5);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorChunkGreaterThanLimit(): void {
        $data     = range(1, 10);
        $executor = Mockery::spy(function (array $variables = []) use ($data): array {
            $this->assertEquals(2, $variables['limit']);

            return array_slice($data, $variables['offset'] ?? 0, $variables['limit']);
        });

        $expected = [1, 2];
        $iterator = (new OffsetBasedObjectIterator(Closure::fromCallable($executor)))
            ->setChunkSize(50)
            ->setLimit(2);
        $actual   = iterator_to_array($iterator);

        $this->assertEquals($expected, $actual);

        $executor->shouldHaveBeenCalled()->times(1);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorLimitZero(): void {
        $executor = Mockery::spy(static function (): array {
            return [];
        });

        $expected = [];
        $iterator = (new OffsetBasedObjectIterator(Closure::fromCallable($executor)))->setLimit(0);
        $actual   = iterator_to_array($iterator);

        $this->assertEquals($expected, $actual);

        $executor->shouldNotHaveBeenCalled();
    }

    /**
     * @covers ::getIterator
     */
    public function testGetIteratorInfiniteLoop(): void {
        $data     = range(1, 10);
        $executor = Mockery::spy(static function () use ($data): array {
            return $data;
        });

        $iterator = (new OffsetBasedObjectIterator(Closure::fromCallable($executor)))->setChunkSize(5);
        $expected = $data;
        $actual   = iterator_to_array($iterator);

        $this->assertEquals($expected, $actual);

        $executor->shouldHaveBeenCalled()->times(2);
    }

    /**
     * @covers ::setOffset
     */
    public function testSetOffset(): void {
        $iterator = new OffsetBasedObjectIterator(static function (): array {
            return [];
        });

        $this->assertNotNull($iterator->setOffset('123'));
    }

    /**
     * @covers ::setOffset
     */
    public function testSetOffsetInvalidType(): void {
        $this->expectException(InvalidArgumentException::class);

        $iterator = new OffsetBasedObjectIterator(static function (): array {
            return [];
        });

        $iterator->setOffset('invalid');
    }
}
