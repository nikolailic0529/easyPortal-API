<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use Closure;
use InvalidArgumentException;
use Mockery;
use Psr\Log\LoggerInterface;
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
        $data   = range(1, 10);
        $logger = $this->app->make(LoggerInterface::class);
        $client = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $client
            ->shouldReceive('call')
            ->times(3)
            ->andReturnUsing(static function (string $selector, string $graphql, array $params = []) use ($data) {
                return array_slice($data, $params['offset'] ?? 0, $params['limit']);
            });

        $expected = $data;
        $actual   = iterator_to_array((new OffsetBasedIterator($logger, $client, '', ''))->chunk(5));

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorWithLimitOffset(): void {
        $data   = range(1, 10);
        $logger = $this->app->make(LoggerInterface::class);
        $client = Mockery::mock(Client::class);
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
        $iterator      = (new OffsetBasedIterator($logger, $client, '', ''))
            ->beforeChunk(Closure::fromCallable($onBeforeChunk))
            ->afterChunk(Closure::fromCallable($onAfterChunk))
            ->offset(5)
            ->limit(2)
            ->chunk(5);

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
        $data   = range(1, 10);
        $logger = $this->app->make(LoggerInterface::class);
        $client = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $client
            ->shouldReceive('call')
            ->times(5)
            ->andReturnUsing(function (string $selector, string $graphql, array $params = []) use ($data) {
                $this->assertEquals(2, $params['limit']);

                return array_slice($data, $params['offset'] ?? 0, $params['limit']);
            });

        $expected = $data;
        $actual   = iterator_to_array((new OffsetBasedIterator($logger, $client, '', ''))->limit(10)->chunk(2));

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorChunkGreaterThanLimit(): void {
        $data   = range(1, 10);
        $logger = $this->app->make(LoggerInterface::class);
        $client = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $client
            ->shouldReceive('call')
            ->times(1)
            ->andReturnUsing(function (string $selector, string $graphql, array $params = []) use ($data) {
                $this->assertEquals(2, $params['limit']);

                return array_slice($data, $params['offset'] ?? 0, $params['limit']);
            });

        $expected = [1, 2];
        $actual   = iterator_to_array((new OffsetBasedIterator($logger, $client, '', ''))->limit(2)->chunk(50));

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::offset
     */
    public function testOffsetInvalidType(): void {
        $this->expectException(InvalidArgumentException::class);

        $logger = $this->app->make(LoggerInterface::class);
        $client = Mockery::mock(Client::class);

        (new OffsetBasedIterator($logger, $client, '', ''))->offset('invalid');
    }
}
