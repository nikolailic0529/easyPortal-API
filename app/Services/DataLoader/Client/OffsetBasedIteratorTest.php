<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

use function array_slice;
use function iterator_to_array;
use function range;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Client\OffsetBasedIterator
 */
class OffsetBasedIteratorTest extends TestCase {
    /**
     * @covers ::getIterator
     */
    public function testGetIterator(): void {
        $data    = range(1, 10);
        $handler = Mockery::mock(ExceptionHandler::class);
        $client  = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $client
            ->shouldReceive('call')
            ->times(3)
            ->andReturnUsing(static function (string $selector, string $graphql, array $params = []) use ($data) {
                return array_slice($data, $params['offset'] ?? 0, $params['limit']);
            });

        $expected = $data;
        $actual   = iterator_to_array((new OffsetBasedIterator($handler, $client, '', ''))->setChunkSize(5));

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorWithLimitOffset(): void {
        $data    = range(1, 10);
        $handler = Mockery::mock(ExceptionHandler::class);
        $client  = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $client
            ->shouldReceive('call')
            ->times(1)
            ->andReturnUsing(static function (string $selector, string $graphql, array $params = []) use ($data) {
                return array_slice($data, $params['offset'], $params['limit']);
            });

        $onBeforeChunk = Mockery::spy(static function (): void {
            // empty
        });
        $onAfterChunk  = Mockery::spy(static function (): void {
            // empty
        });
        $iterator      = (new OffsetBasedIterator($handler, $client, '', ''))
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
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorChunkLessThanLimit(): void {
        $data    = range(1, 10);
        $handler = Mockery::mock(ExceptionHandler::class);
        $client  = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $client
            ->shouldReceive('call')
            ->times(5)
            ->andReturnUsing(function (string $selector, string $graphql, array $params = []) use ($data) {
                $this->assertEquals(2, $params['limit']);

                return array_slice($data, $params['offset'] ?? 0, $params['limit']);
            });

        $expected = $data;
        $iterator = (new OffsetBasedIterator($handler, $client, '', ''))->setLimit(10)->setChunkSize(2);
        $actual   = iterator_to_array($iterator);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorChunkGreaterThanLimit(): void {
        $data    = range(1, 10);
        $handler = Mockery::mock(ExceptionHandler::class);
        $client  = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $client
            ->shouldReceive('call')
            ->times(1)
            ->andReturnUsing(function (string $selector, string $graphql, array $params = []) use ($data) {
                $this->assertEquals(2, $params['limit']);

                return array_slice($data, $params['offset'] ?? 0, $params['limit']);
            });

        $expected = [1, 2];
        $iterator = (new OffsetBasedIterator($handler, $client, '', ''))->setLimit(2)->setChunkSize(50);
        $actual   = iterator_to_array($iterator);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorLimitZero(): void {
        $handler = Mockery::mock(ExceptionHandler::class);
        $client  = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $client
            ->shouldReceive('call')
            ->never();

        $expected = [];
        $iterator = (new OffsetBasedIterator($handler, $client, '', ''))->setLimit(0);
        $actual   = iterator_to_array($iterator);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::setOffset
     */
    public function testSetOffset(): void {
        $handler  = Mockery::mock(ExceptionHandler::class);
        $client   = Mockery::mock(Client::class);
        $iterator = new OffsetBasedIterator($handler, $client, '', '');

        $this->assertNotNull($iterator->setOffset('123'));
    }

    /**
     * @covers ::setOffset
     */
    public function testSetOffsetInvalidType(): void {
        $this->expectException(InvalidArgumentException::class);

        $handler = Mockery::mock(ExceptionHandler::class);
        $client  = Mockery::mock(Client::class);

        (new OffsetBasedIterator($handler, $client, '', ''))->setOffset('invalid');
    }
}
