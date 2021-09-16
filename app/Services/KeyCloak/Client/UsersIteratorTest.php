<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client;

use Closure;
use Mockery;
use Tests\TestCase;
use TypeError;

use function array_slice;
use function iterator_to_array;
use function range;

/**
 * @internal
 * @coversDefaultClass \App\Services\KeyCloak\Client\UsersIterator
 */
class UsersIteratorTest extends TestCase {
    /**
     * @covers ::getIterator
     */
    public function testGetIterator(): void {
        $data   = range(1, 10);
        $client = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $client
            ->shouldReceive('getUsers')
            ->times(3)
            ->andReturnUsing(static function ($chunk, $offset) use ($data) {
                return array_slice($data, $offset, $chunk);
            });

        $expected = $data;
        $actual   = iterator_to_array((new UsersIterator($client))->setChunkSize(5));

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorWithLimitOffset(): void {
        $data   = range(1, 10);
        $client = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $offset = 5;
        $limit  = 2;
        $chunk  = 5;
        $client
            ->shouldReceive('getUsers')
            ->times(1)
            ->andReturnUsing(static function ($chunk, $offset) use ($data, $limit) {
                return array_slice($data, $offset, $limit);
            });

        $onBeforeChunk = Mockery::spy(static function (): void {
            // empty
        });
        $onAfterChunk  = Mockery::spy(static function (): void {
            // empty
        });
        $iterator      = (new UsersIterator($client))
            ->onBeforeChunk(Closure::fromCallable($onBeforeChunk))
            ->onAfterChunk(Closure::fromCallable($onAfterChunk))
            ->setOffset($offset)
            ->setLimit($limit)
            ->setChunkSize($chunk);

        $expected = [6, 7];
        $actual   = iterator_to_array($iterator);

        $this->assertEquals($expected, $actual);

        $onBeforeChunk->shouldHaveBeenCalled();
        $onAfterChunk->shouldHaveBeenCalled();
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorChunkGreaterThanLimit(): void {
        $data   = range(1, 10);
        $client = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $chunk = 50;
        $limit = 2;

        $client
            ->shouldReceive('getUsers')
            ->times(1)
            ->andReturnUsing(static function ($chunk, $offset) use ($data, $limit) {
                return array_slice($data, $offset, $limit);
            });

        $expected = [1, 2];
        $iterator = (new UsersIterator($client))->setLimit($limit)->setChunkSize($chunk);
        $actual   = iterator_to_array($iterator);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorLimitZero(): void {
        $client = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $client
            ->shouldReceive('getUsers')
            ->never();

        $expected = [];
        $iterator = (new UsersIterator($client))->setLimit(0);
        $actual   = iterator_to_array($iterator);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::setOffset
     */
    public function testSetOffset(): void {
        $client   = Mockery::mock(Client::class);
        $iterator = new UsersIterator($client);

        $this->assertNotNull($iterator->setOffset(123));
    }

    /**
     * @covers ::setOffset
     */
    public function testSetOffsetInvalidType(): void {
        $this->expectException(TypeError::class);

        $client = Mockery::mock(Client::class);

        (new UsersIterator($client))->setOffset('invalid');
    }
}
