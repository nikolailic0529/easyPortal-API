<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use Closure;
use Mockery;
use Tests\TestCase;

use function array_map;
use function array_slice;
use function is_array;
use function is_string;
use function iterator_to_array;
use function range;

/**
 * @internal
 * @covers \App\Services\DataLoader\Client\LastIdBasedIterator
 */
class LastIdBasedIteratorTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testGetIterator(): void {
        $data     = $this->getData();
        $executor = Mockery::spy($this->getRetriever($data));
        $expected = $data;
        $actual   = iterator_to_array(
            (new LastIdBasedIterator(Closure::fromCallable($executor)))->setChunkSize(5),
        );

        self::assertEquals($expected, $actual);

        $executor
            ->shouldHaveBeenCalled()
            ->times(3);
    }

    public function testGetIteratorWithLimitLastId(): void {
        $data          = $this->getData();
        $executor      = Mockery::spy($this->getRetriever($data));
        $onBeforeChunk = Mockery::spy(static function (): void {
            // empty
        });
        $onAfterChunk  = Mockery::spy(static function (): void {
            // empty
        });
        $iterator      = (new LastIdBasedIterator(Closure::fromCallable($executor)))
            ->onBeforeChunk(Closure::fromCallable($onBeforeChunk))
            ->onAfterChunk(Closure::fromCallable($onAfterChunk))
            ->setOffset('5')
            ->setLimit(2)
            ->setChunkSize(5);

        $expected = ['6', '7'];
        $actual   = iterator_to_array($iterator);
        $actual   = array_map(static function (mixed $type): ?string {
            return is_array($type) && isset($type['id']) && is_string($type['id'])
                ? $type['id']
                : null;
        }, $actual);

        self::assertEquals($expected, $actual);

        $executor
            ->shouldHaveBeenCalled()
            ->times(1);
        $onBeforeChunk->shouldHaveBeenCalled();
        $onAfterChunk->shouldHaveBeenCalled();
    }

    public function testGetIteratorChunkLessThanLimit(): void {
        $data     = $this->getData();
        $executor = Mockery::spy(function (array $params = []) use ($data) {
            self::assertEquals(2, $params['limit']);

            return $this->getRetriever($data)($params);
        });

        $expected = $data;
        $iterator = (new LastIdBasedIterator(Closure::fromCallable($executor)))
            ->setLimit(10)
            ->setChunkSize(2);
        $actual   = iterator_to_array($iterator);

        self::assertEquals($expected, $actual);

        $executor
            ->shouldHaveBeenCalled()
            ->times(5);
    }

    public function testGetIteratorChunkGreaterThanLimit(): void {
        $data     = $this->getData();
        $executor = Mockery::spy(function (array $params = []) use ($data) {
            self::assertEquals(2, $params['limit']);

            return $this->getRetriever($data)($params);
        });

        $expected = ['1', '2'];
        $iterator = (new LastIdBasedIterator(Closure::fromCallable($executor)))
            ->setLimit(2)
            ->setChunkSize(50);
        $actual   = iterator_to_array($iterator);
        $actual   = array_map(static function (mixed $type): ?string {
            return is_array($type) && isset($type['id']) && is_string($type['id'])
                ? $type['id']
                : null;
        }, $actual);

        self::assertEquals($expected, $actual);

        $executor
            ->shouldHaveBeenCalled()
            ->times(1);
    }

    public function testGetIteratorLimitZero(): void {
        $executor = Mockery::spy(static function (array $params = []): mixed {
            return null;
        });

        $expected = [];
        $iterator = (new LastIdBasedIterator(Closure::fromCallable($executor)))->setLimit(0);
        $actual   = iterator_to_array($iterator);

        self::assertEquals($expected, $actual);

        $executor->shouldNotHaveBeenCalled();
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @return array<int,array<string, mixed>>
     */
    protected function getData(): array {
        return array_map(static function (int $id): array {
            return ['id' => (string) $id];
        }, range(1, 10));
    }

    /**
     * @param array<int,array<string, mixed>> $data
     */
    protected function getRetriever(array $data): Closure {
        return static function (array $params = []) use ($data) {
            $index = 0;

            if ($params['lastId']) {
                foreach ($data as $i => $type) {
                    if (isset($type['id']) && $type['id'] === $params['lastId']) {
                        $index = $i + 1;
                        break;
                    }
                }
            }

            return array_slice($data, $index, $params['limit'], true);
        };
    }
    //</editor-fold>
}
