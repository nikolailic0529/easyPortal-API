<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Eloquent;

use App\Models\Data\Type;
use App\Utils\Eloquent\Callbacks\GetKey;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use LastDragon_ru\LaraASP\Eloquent\Iterators\Iterator;
use Mockery;
use Tests\TestCase;
use Tests\WithQueryLogs;

use function array_fill;
use function iterator_to_array;

/**
 * @internal
 * @covers \App\Utils\Iterators\Eloquent\EloquentIterator
 */
class EloquentIteratorTest extends TestCase {
    use WithQueryLogs;

    public function testGetIterator(): void {
        // Prepare
        $models   = (new Collection(array_fill(0, 10, null)))
            ->map(static function (): Type {
                return Type::factory()->create();
            })
            ->map(new GetKey())
            ->sort()
            ->values();
        $builder  = Type::query()->orderBy((new Type())->getKeyName());
        $iterator = new EloquentIterator($builder->getChunkedIterator());

        // All
        $queries  = $this->getQueryLog();
        $expected = $models;
        $actual   = (clone $iterator);
        $actual   = (new Collection($actual))->map(new GetKey());

        self::assertEquals($expected, $actual);

        self::assertCount(1, $queries);

        $queries->flush();

        // All chunked
        $queries  = $this->getQueryLog();
        $expected = $models;
        $actual   = (clone $iterator)->setChunkSize(4);
        $actual   = (new Collection($actual))->map(new GetKey())->sort()->values();

        self::assertEquals($expected, $actual);

        self::assertCount(3, $queries);

        $queries->flush();

        // Part
        $queries  = $this->getQueryLog();
        $expected = $models->slice(2, 5)->values();
        $actual   = (clone $iterator)->setOffset(2)->setLimit(5)->setChunkSize(4);
        $actual   = (new Collection($actual))->map(new GetKey())->sort()->values();

        self::assertEquals($expected, $actual);

        self::assertCount(2, $queries);

        $queries->flush();
    }

    public function testEvents(): void {
        $init   = Mockery::spy(static function (): void {
            // empty
        });
        $finish = Mockery::spy(static function (): void {
            // empty
        });
        $before = Mockery::spy(static function (): void {
            // empty
        });
        $after  = Mockery::spy(static function (): void {
            // empty
        });

        $models   = (new Collection(array_fill(0, 7, null)))
            ->map(static function (): Type {
                return Type::factory()->create();
            })
            ->map(new GetKey())
            ->sort()
            ->values();
        $builder  = Type::query()->orderBy((new Type())->getKeyName());
        $iterator = (new EloquentIterator($builder->getChunkedIterator()))
            ->onInit(Closure::fromCallable($init))
            ->onFinish(Closure::fromCallable($finish))
            ->onBeforeChunk(Closure::fromCallable($before))
            ->onAfterChunk(Closure::fromCallable($after))
            ->setChunkSize(2)
            ->setOffset(2)
            ->setLimit(3);

        iterator_to_array($iterator);

        $chunkA = static function (array $items) use ($models): bool {
            $expected = $models->slice(2, 2)->values()->all();
            $actual   = (new Collection($items))->map(new GetKey())->all();

            return $expected === $actual;
        };
        $chunkB = static function (array $items) use ($models): bool {
            $expected = $models->slice(4, 1)->values()->all();
            $actual   = (new Collection($items))->map(new GetKey())->all();

            return $expected === $actual;
        };

        $init
            ->shouldHaveBeenCalled()
            ->once();
        $finish
            ->shouldHaveBeenCalled()
            ->once();
        $before
            ->shouldHaveBeenCalled()
            ->withArgs($chunkA)
            ->once();
        $before
            ->shouldHaveBeenCalled()
            ->withArgs($chunkB)
            ->once();
        $after
            ->shouldHaveBeenCalled()
            ->withArgs($chunkA)
            ->once();
        $after
            ->shouldHaveBeenCalled()
            ->withArgs($chunkB)
            ->once();
    }

    public function testClone(): void {
        $builder  = Type::query();
        $iterator = new class($builder->getChunkedIterator()) extends EloquentIterator {
            /**
             * @return Iterator<Model>
             */
            public function getInternalIterator(): Iterator {
                return $this->iterator;
            }
        };
        $clone    = clone $iterator;

        self::assertNotSame($iterator->getInternalIterator(), $clone->getInternalIterator());
    }

    public function testGetCount(): void {
        $builder  = Type::query();
        $iterator = new EloquentIterator($builder->getChunkedIterator());

        Type::factory()->count(2)->create();

        self::assertEquals(2, $iterator->getCount());
        self::assertEquals(1, $iterator->setLimit(1)->getCount());
    }
}
