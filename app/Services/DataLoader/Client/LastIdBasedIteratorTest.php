<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Schema\Type;
use Closure;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

use function array_map;
use function array_slice;
use function iterator_to_array;
use function range;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Client\LastIdBasedIterator
 */
class LastIdBasedIteratorTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::getIterator
     */
    public function testGetIterator(): void {
        $data   = $this->getData();
        $logger = $this->app->make(LoggerInterface::class);
        $client = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $client
            ->shouldReceive('call')
            ->times(3)
            ->andReturnUsing($this->getRetriever($data));

        $expected = $data;
        $actual   = iterator_to_array((new LastIdBasedIterator($logger, $client, '', ''))->chunk(5));

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorWithLimitLastId(): void {
        $data   = $this->getData();
        $logger = $this->app->make(LoggerInterface::class);
        $client = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $client
            ->shouldReceive('call')
            ->times(1)
            ->andReturnUsing($this->getRetriever($data));

        $expected = ['6', '7'];
        $actual   = iterator_to_array(
            (new LastIdBasedIterator($logger, $client, '', ''))->lastId('5')->limit(2)->chunk(5),
        );
        $actual   = array_map(static function (Type $type): ?string {
            return $type->id ?? null;
        }, $actual);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorChunkLessThanLimit(): void {
        $data   = $this->getData();
        $logger = $this->app->make(LoggerInterface::class);
        $client = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $client
            ->shouldReceive('call')
            ->times(5)
            ->andReturnUsing(function (string $selector, string $graphql, array $params = []) use ($data) {
                $this->assertEquals(2, $params['limit']);

                return $this->getRetriever($data)($selector, $graphql, $params);
            });

        $expected = $data;
        $actual   = iterator_to_array((new LastIdBasedIterator($logger, $client, '', ''))->limit(10)->chunk(2));

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::iterator
     */
    public function testIteratorChunkGreaterThanLimit(): void {
        $data   = $this->getData();
        $logger = $this->app->make(LoggerInterface::class);
        $client = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();

        $client
            ->shouldReceive('call')
            ->times(1)
            ->andReturnUsing(function (string $selector, string $graphql, array $params = []) use ($data) {
                $this->assertEquals(2, $params['limit']);

                return $this->getRetriever($data)($selector, $graphql, $params);
            });

        $expected = ['1', '2'];
        $actual   = iterator_to_array((new LastIdBasedIterator($logger, $client, '', ''))->limit(2)->chunk(50));
        $actual   = array_map(static function (Type $type): ?string {
            return $type->id ?? null;
        }, $actual);

        $this->assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @return array<int,\App\Services\DataLoader\Schema\Type>
     */
    protected function getData(): array {
        return array_map(static function (int $id): Type {
            return new class(['id' => (string) $id]) extends Type {
                public string $id;
            };
        }, range(1, 10));
    }

    /**
     * @param array<int,\App\Services\DataLoader\Schema\Type> $data
     */
    protected function getRetriever(array $data): Closure {
        return static function (string $selector, string $graphql, array $params = []) use ($data) {
            $index = 0;

            if ($params['lastId']) {
                foreach ($data as $i => $type) {
                    if (isset($type->id) && $type->id === $params['lastId']) {
                        $index = $i + 1;
                        break;
                    }
                }
            }

            return array_slice($data, $index, $params['limit']);
        };
    }
    //</editor-fold>
}
