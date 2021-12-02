<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use Generator;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

use function array_slice;
use function iterator_to_array;
use function sprintf;

/**
 * @internal
 * @coversDefaultClass \App\Utils\Iterators\ObjectIteratorIterator
 */
class ObjectIteratorIteratorTest extends TestCase {
    /**
     * @covers ::getIterator
     */
    public function testGetIterator(): void {
        $iterator = new ObjectIteratorIterator([
            'one' => new ObjectIteratorIteratorTest__Iterator([1, 2, 3, 4, 5]),
            'two' => new ObjectIteratorIteratorTest__Iterator([6, 7, 8, 9, 0]),
        ]);

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 0], iterator_to_array($iterator));
        $this->assertEquals([3, 4, 5, 6, 7, 8], iterator_to_array(
            $iterator->setOffset('one@2')->setLimit(6),
        ));
        $this->assertEquals([3, 4, 5, 6, 7, 8, 9, 0], iterator_to_array(
            $iterator->setOffset('one@2')->setLimit(null),
        ));
        $this->assertEquals([6, 7, 8, 9, 0], iterator_to_array(
            $iterator->setOffset('two')->setLimit(null),
        ));
        $this->assertEquals([9, 0], iterator_to_array(
            $iterator->setOffset('two@3')->setLimit(null),
        ));
    }

    /**
     * @covers ::getIterator
     */
    public function testGetIteratorPropertiesPromotion(): void {
        $before = static function (): void {
            // empty
        };
        $after  = static function (): void {
            // empty
        };
        $one    = Mockery::mock(ObjectIterator::class);
        $one
            ->shouldReceive('setIndex')
            ->with(0)
            ->twice()
            ->andReturnSelf();
        $one
            ->shouldReceive('setLimit')
            ->with(null)
            ->once()
            ->andReturnSelf();
        $one
            ->shouldReceive('setLimit')
            ->with(7)
            ->once()
            ->andReturnSelf();
        $one
            ->shouldReceive('setOffset')
            ->with(null)
            ->twice()
            ->andReturnSelf();
        $one
            ->shouldReceive('setChunkSize')
            ->with(7)
            ->once()
            ->andReturnSelf();
        $one
            ->shouldReceive('onBeforeChunk')
            ->with($before)
            ->once()
            ->andReturnSelf();
        $one
            ->shouldReceive('onAfterChunk')
            ->with($after)
            ->once()
            ->andReturnSelf();
        $one
            ->shouldReceive('getIterator')
            ->once()
            ->andReturnUsing(static function (): Generator {
                yield from [1, 2, 3, 4, 5];
            });

        $two = Mockery::mock(ObjectIterator::class);
        $two
            ->shouldReceive('setIndex')
            ->with(0)
            ->twice()
            ->andReturnSelf();
        $two
            ->shouldReceive('setLimit')
            ->with(null)
            ->once()
            ->andReturnSelf();
        $two
            ->shouldReceive('setLimit')
            ->with(2)
            ->once()
            ->andReturnSelf();
        $two
            ->shouldReceive('setOffset')
            ->with(null)
            ->twice()
            ->andReturnSelf();
        $two
            ->shouldReceive('setChunkSize')
            ->with(7)
            ->once()
            ->andReturnSelf();
        $two
            ->shouldReceive('onBeforeChunk')
            ->with($before)
            ->once()
            ->andReturnSelf();
        $two
            ->shouldReceive('onAfterChunk')
            ->with($after)
            ->once()
            ->andReturnSelf();
        $two
            ->shouldReceive('getIterator')
            ->once()
            ->andReturnUsing(static function (): Generator {
                yield from [6, 7];
            });

        $iterator = (new ObjectIteratorIterator([
            'one' => $one,
            'two' => $two,
        ]))
            ->onBeforeChunk($before)
            ->onAfterChunk($after)
            ->setChunkSize(123)
            ->setLimit(7);

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7], iterator_to_array($iterator));
    }


    /**
     * @covers ::getOffset
     * @covers ::setOffset
     */
    public function testOffset(): void {
        // Mocks
        $one = Mockery::mock(ObjectIterator::class);
        $one
            ->shouldReceive('setIndex')
            ->with(0)
            ->times(3)
            ->andReturnSelf();
        $one
            ->shouldReceive('setOffset')
            ->with(null)
            ->times(3)
            ->andReturnSelf();
        $one
            ->shouldReceive('setOffset')
            ->with(123)
            ->once()
            ->andReturnSelf();
        $one
            ->shouldReceive('getOffset')
            ->twice()
            ->andReturn(123);

        $two = Mockery::mock(ObjectIterator::class);
        $two
            ->shouldReceive('setIndex')
            ->with(0)
            ->times(3)
            ->andReturnSelf();
        $two
            ->shouldReceive('setOffset')
            ->with(null)
            ->times(4)
            ->andReturnSelf();
        $two
            ->shouldReceive('getOffset')
            ->once()
            ->andReturn(null);

        // Prepare
        $iterator = new ObjectIteratorIterator([
            'one' => $one,
            'two' => $two,
        ]);

        // Simple test
        $this->assertNull($iterator->getOffset());
        $this->assertEquals('two', $iterator->setOffset('two')->getOffset());
        $this->assertEquals('one@123', $iterator->setOffset('one@123')->getOffset());
    }

    /**
     * @covers ::setOffset
     */
    public function testSetOffsetUnknownIterator(): void {
        $this->expectExceptionObject(new InvalidArgumentException(sprintf(
            'The `$offset` is not valid, iterator `%s` is unknown.',
            'unknown',
        )));

        (new ObjectIteratorIterator([]))->setOffset('unknown');
    }

    /**
     * @covers ::setOffset
     */
    public function testSetOffsetInvalidType(): void {
        $this->expectExceptionObject(new InvalidArgumentException(sprintf(
            'The `$offset` must be `string` or `null`, `%s` given',
            'integer',
        )));

        (new ObjectIteratorIterator([]))->setOffset(123);
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ObjectIteratorIteratorTest__Iterator implements ObjectIterator {
    use ObjectIteratorProperties;
    use ObjectIteratorSubjects;

    /**
     * @param array<mixed> $data
     */
    public function __construct(
        protected array $data = [],
    ) {
        // empty
    }

    public function getIterator(): Generator {
        yield from array_slice($this->data, (int) $this->offset, $this->limit);
    }
}
