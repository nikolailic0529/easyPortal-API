<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

use function iterator_to_array;
use function range;

/**
 * @internal
 * @coversDefaultClass \App\Utils\Iterators\OneChunkOffsetBasedObjectIterator
 */
class OneChunkOffsetBasedObjectIteratorTest extends TestCase {
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
        $executor = Mockery::spy(function (array $variables = []) use ($data): array {
            $this->assertEmpty($variables);

            return $data;
        });
        $iterator = (new OneChunkOffsetBasedObjectIterator(
            Mockery::mock(ExceptionHandler::class),
            Closure::fromCallable($executor),
        ))
            ->setChunkSize(5)
            ->onInit(Closure::fromCallable($onInit))
            ->onFinish(Closure::fromCallable($onFinish));

        $expected = $data;
        $actual   = iterator_to_array($iterator);
        $second   = iterator_to_array($iterator);

        $this->assertEquals($expected, $actual);
        $this->assertEquals($second, $actual);

        $onInit->shouldHaveBeenCalled()->twice();
        $onFinish->shouldHaveBeenCalled()->twice();
        $executor->shouldHaveBeenCalled()->times(1);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorWithLimitOffset(): void {
        $data     = range(1, 10);
        $executor = Mockery::spy(function (array $variables = []) use ($data): array {
            $this->assertEmpty($variables);

            return $data;
        });

        $onBeforeChunk = Mockery::spy(static function (): void {
            // empty
        });
        $onAfterChunk  = Mockery::spy(static function (): void {
            // empty
        });
        $iterator      = (new OneChunkOffsetBasedObjectIterator(
            Mockery::mock(ExceptionHandler::class),
            Closure::fromCallable($executor),
        ))
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
        $iterator = (new OneChunkOffsetBasedObjectIterator(
            Mockery::mock(ExceptionHandler::class),
            Closure::fromCallable($executor),
        ))
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
        $iterator = (new OneChunkOffsetBasedObjectIterator(
            Mockery::mock(ExceptionHandler::class),
            Closure::fromCallable($executor),
        ))
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
        $iterator = (new OneChunkOffsetBasedObjectIterator(
            Mockery::mock(ExceptionHandler::class),
            Closure::fromCallable($executor),
        ))->setLimit(0);
        $actual   = iterator_to_array($iterator);

        $this->assertEquals($expected, $actual);

        $executor->shouldNotHaveBeenCalled();
    }

    /**
     * @covers ::setOffset
     */
    public function testSetOffset(): void {
        $handler  = Mockery::mock(ExceptionHandler::class);
        $iterator = new OneChunkOffsetBasedObjectIterator($handler, static function (): array {
            return [];
        });

        $this->assertNotNull($iterator->setOffset('123'));
    }

    /**
     * @covers ::setOffset
     */
    public function testSetOffsetInvalidType(): void {
        $this->expectException(InvalidArgumentException::class);

        $handler  = Mockery::mock(ExceptionHandler::class);
        $iterator = new OneChunkOffsetBasedObjectIterator($handler, static function (): array {
            return [];
        });

        $iterator->setOffset('invalid');
    }
}
