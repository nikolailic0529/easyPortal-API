<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use Mockery;
use Tests\TestCase;

use function array_slice;
use function iterator_to_array;
use function range;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Client\Iterator
 */
class IteratorTest extends TestCase {
    /**
     * @covers ::getIterator
     */
    public function testGetIterator(): void {
        $data   = range(1, 10);
        $client = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $client
            ->shouldReceive('call')
            ->times(3)
            ->andReturnUsing(static function (string $selector, string $graphql, array $params = []) use ($data) {
                return array_slice($data, $params['offset'], $params['limit']);
            });

        $expected = $data;
        $actual   = iterator_to_array((new Iterator($client, '', ''))->chunk(5));

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorWithLimitOffset(): void {
        $data   = range(1, 10);
        $client = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $client
            ->shouldReceive('call')
            ->times(1)
            ->andReturnUsing(static function (string $selector, string $graphql, array $params = []) use ($data) {
                return array_slice($data, $params['offset'], $params['limit']);
            });

        $expected = [6, 7];
        $actual   = iterator_to_array((new Iterator($client, '', ''))->offset(5)->limit(2)->chunk(5));

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorChunkLessThanLimit(): void {
        $data   = range(1, 10);
        $client = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $client
            ->shouldReceive('call')
            ->times(5)
            ->andReturnUsing(function (string $selector, string $graphql, array $params = []) use ($data) {
                $this->assertEquals(2, $params['limit']);

                return array_slice($data, $params['offset'], $params['limit']);
            });

        $expected = $data;
        $actual   = iterator_to_array((new Iterator($client, '', ''))->limit(10)->chunk(2));

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorChunkGreaterThanLimit(): void {
        $data   = range(1, 10);
        $client = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $client
            ->shouldReceive('call')
            ->times(1)
            ->andReturnUsing(function (string $selector, string $graphql, array $params = []) use ($data) {
                $this->assertEquals(2, $params['limit']);

                return array_slice($data, $params['offset'], $params['limit']);
            });

        $expected = [1, 2];
        $actual   = iterator_to_array((new Iterator($client, '', ''))->limit(2)->chunk(50));

        $this->assertEquals($expected, $actual);
    }
}
