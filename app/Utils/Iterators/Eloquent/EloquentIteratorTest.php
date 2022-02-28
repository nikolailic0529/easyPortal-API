<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Eloquent;

use App\Models\Type;
use App\Utils\Eloquent\Callbacks\GetKey;
use Closure;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;
use Tests\WithQueryLogs;

use function iterator_to_array;

/**
 * @internal
 * @coversDefaultClass \App\Utils\Iterators\Eloquent\EloquentIterator
 */
class EloquentIteratorTest extends TestCase {
    use WithQueryLogs;

    /**
     * @covers ::getIterator
     * @covers ::setOffset
     * @covers ::setLimit
     */
    public function testGetIterator(): void {
        // Prepare
        $models   = Type::factory()->count(10)->create()->map(new GetKey())->sort()->values();
        $builder  = Type::query()->orderBy((new Type())->getKeyName());
        $iterator = new EloquentIterator($builder->getChunkedIterator());

        // All
        $queries  = $this->getQueryLog();
        $expected = $models;
        $actual   = (clone $iterator);
        $actual   = (new Collection($actual))->map(new GetKey());

        $this->assertEquals($expected, $actual);

        $this->assertCount(2, $queries);

        $queries->flush();

        // All chunked
        $queries  = $this->getQueryLog();
        $expected = $models;
        $actual   = (clone $iterator)->setChunkSize(4);
        $actual   = (new Collection($actual))->map(new GetKey())->sort()->values();

        $this->assertEquals($expected, $actual);

        $this->assertCount(4, $queries);

        $queries->flush();

        // Part
        $queries  = $this->getQueryLog();
        $expected = $models->slice(2, 5)->values();
        $actual   = (clone $iterator)->setOffset(2)->setLimit(5)->setChunkSize(4);
        $actual   = (new Collection($actual))->map(new GetKey())->sort()->values();

        $this->assertEquals($expected, $actual);

        $this->assertCount(2, $queries);

        $queries->flush();
    }

    /**
     * @covers ::onInit
     * @covers ::onFinish
     * @covers ::onBeforeChunk
     * @covers ::onAfterChunk
     */
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

        $models   = Type::factory()->count(7)->create()->map(new GetKey())->sort()->values();
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
}
