<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use App\Utils\Iterators\Concerns\Properties;
use App\Utils\Iterators\Concerns\Subjects;
use App\Utils\Iterators\Contracts\ObjectIterator;
use Generator;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

use function array_slice;
use function iterator_to_array;
use function sprintf;

/**
 * @internal
 * @coversDefaultClass \App\Utils\Iterators\ObjectIteratorsIterator
 */
class ObjectIteratorsIteratorTest extends TestCase {
    /**
     * @covers ::getIterator
     */
    public function testGetIterator(): void {
        $iterator = new ObjectIteratorsIterator([
            'one' => new ObjectIteratorsIteratorTest__Iterator([1, 2, 3, 4, 5]),
            'two' => new ObjectIteratorsIteratorTest__Iterator([6, 7, 8, 9, 0]),
        ]);

        self::assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 0], iterator_to_array($iterator));
        self::assertEquals([3, 4, 5, 6, 7, 8], iterator_to_array(
            $iterator->setOffset('one@2')->setLimit(6),
        ));
        self::assertEquals([3, 4, 5, 6, 7, 8, 9, 0], iterator_to_array(
            $iterator->setOffset('one@2')->setLimit(null),
        ));
        self::assertEquals([6, 7, 8, 9, 0], iterator_to_array(
            $iterator->setOffset('two')->setLimit(null),
        ));
        self::assertEquals([9, 0], iterator_to_array(
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
            ->shouldReceive('onInit')
            ->never();
        $one
            ->shouldReceive('onFinish')
            ->never();
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
            ->shouldReceive('onInit')
            ->never();
        $two
            ->shouldReceive('onFinish')
            ->never();
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

        $iterator = (new ObjectIteratorsIterator([
            'one' => $one,
            'two' => $two,
        ]))
            ->onBeforeChunk($before)
            ->onAfterChunk($after)
            ->setChunkSize(123)
            ->setLimit(7);

        self::assertEquals([1, 2, 3, 4, 5, 6, 7], iterator_to_array($iterator));
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
        $iterator = new ObjectIteratorsIterator([
            'one' => $one,
            'two' => $two,
        ]);

        // Simple test
        self::assertNull($iterator->getOffset());
        self::assertEquals('two', $iterator->setOffset('two')->getOffset());
        self::assertEquals('one@123', $iterator->setOffset('one@123')->getOffset());
    }

    /**
     * @covers ::setOffset
     */
    public function testSetOffsetUnknownIterator(): void {
        self::expectExceptionObject(new InvalidArgumentException(sprintf(
            'The `$offset` is not valid, iterator `%s` is unknown.',
            'unknown',
        )));

        (new ObjectIteratorsIterator([]))->setOffset('unknown');
    }

    /**
     * @covers ::setOffset
     */
    public function testSetOffsetInvalidType(): void {
        self::expectExceptionObject(new InvalidArgumentException(sprintf(
            'The `$offset` must be `string` or `null`, `%s` given',
            'integer',
        )));

        (new ObjectIteratorsIterator([]))->setOffset(123);
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 *
 * @template TItem
 *
 * @implements ObjectIterator<TItem>
 */
class ObjectIteratorsIteratorTest__Iterator implements ObjectIterator {
    /**
     * @phpstan-use Subjects<TItem>
     */
    use Subjects;

    use Properties;

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
