<?php declare(strict_types = 1);

namespace App\GraphQL\Utils\Iterators;

use Closure;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

use function iterator_to_array;
use function range;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Utils\Iterators\QueryBasedIterator
 */
class QueryBasedIteratorTest extends TestCase {
    /**
     * @covers ::getIterator
     */
    public function testGetIterator(): void {
        $data     = range(1, 10);
        $executor = Mockery::spy(function (array $variables = []) use ($data): array {
            $this->assertEmpty($variables);

            return $data;
        });

        $iterator = (new QueryBasedIterator(Closure::fromCallable($executor)))->setChunkSize(5);
        $expected = $data;
        $actual   = iterator_to_array($iterator);
        $second   = iterator_to_array($iterator);

        $this->assertEquals($expected, $actual);
        $this->assertEquals($second, $actual);

        $executor->shouldHaveBeenCalled()->times(1);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorWithLimitOffset(): void {
        $data          = range(1, 10);
        $executor      = Mockery::spy(function (array $variables = []) use ($data): array {
            $this->assertEmpty($variables);

            return $data;
        });

        $onBeforeChunk = Mockery::spy(static function (): void {
            // empty
        });
        $onAfterChunk  = Mockery::spy(static function (): void {
            // empty
        });
        $iterator      = (new QueryBasedIterator(Closure::fromCallable($executor)))
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
            $this->assertEmpty($variables);

            return $data;
        });

        $expected = $data;
        $iterator = (new QueryBasedIterator(Closure::fromCallable($executor)))
            ->setChunkSize(2)
            ->setLimit(10);
        $actual   = iterator_to_array($iterator);

        $this->assertEquals($expected, $actual);

        $executor->shouldHaveBeenCalled()->times(1);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorChunkGreaterThanLimit(): void {
        $data     = range(1, 10);
        $executor = Mockery::spy(function (array $variables = []) use ($data): array {
            $this->assertEmpty($variables);

            return $data;
        });

        $expected = [1, 2];
        $iterator = (new QueryBasedIterator(Closure::fromCallable($executor)))
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
        $iterator = (new QueryBasedIterator(Closure::fromCallable($executor)))->setLimit(0);
        $actual   = iterator_to_array($iterator);

        $this->assertEquals($expected, $actual);

        $executor->shouldNotHaveBeenCalled();
    }

    /**
     * @covers ::setOffset
     */
    public function testSetOffset(): void {
        $iterator = new QueryBasedIterator(static function (): array {
            return [];
        });

        $this->assertNotNull($iterator->setOffset('123'));
    }

    /**
     * @covers ::setOffset
     */
    public function testSetOffsetInvalidType(): void {
        $this->expectException(InvalidArgumentException::class);

        $iterator = new QueryBasedIterator(static function (): array {
            return [];
        });

        $iterator->setOffset('invalid');
    }
}
