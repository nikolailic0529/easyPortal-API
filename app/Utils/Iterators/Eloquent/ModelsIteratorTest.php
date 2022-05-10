<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Eloquent;

use App\Models\Type;
use App\Utils\Eloquent\Callbacks\GetKey;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;
use Tests\WithQueryLogs;

use function array_fill;
use function count;
use function iterator_to_array;

/**
 * @internal
 * @coversDefaultClass \App\Utils\Iterators\Eloquent\ModelsIterator
 */
class ModelsIteratorTest extends TestCase {
    use WithQueryLogs;

    /**
     * @covers ::getIterator
     * @covers ::setOffset
     * @covers ::setLimit
     */
    public function testGetIterator(): void {
        // Prepare
        $models   = (new Collection(array_fill(0, 10, null)))
            ->map(static function (): Type {
                return Type::factory()->create();
            });
        $handler  = Mockery::mock(ExceptionHandler::class);
        $builder  = Type::query()->orderBy((new Type())->getKeyName());
        $iterator = new ModelsIterator(
            $handler,
            $builder,
            $models->map(new GetKey())->all(),
        );

        // All
        $queries  = $this->getQueryLog();
        $expected = $models->map(new GetKey())->sort()->values();
        $actual   = (clone $iterator);
        $actual   = (new Collection($actual))->map(new GetKey());

        self::assertEquals($expected, $actual);
        self::assertCount(1, $queries);

        $queries->flush();

        // All chunked
        $queries  = $this->getQueryLog();
        $expected = $models->map(new GetKey())->sort()->values();
        $actual   = (clone $iterator)->setChunkSize(4);
        $actual   = (new Collection($actual))->map(new GetKey())->sort()->values();

        self::assertEquals($expected, $actual);
        self::assertCount(3, $queries);

        $queries->flush();

        // Part
        $queries  = $this->getQueryLog();
        $expected = $models->slice(2, 5)->map(new GetKey())->sort()->values();
        $actual   = (clone $iterator)->setOffset(2)->setLimit(5)->setChunkSize(4);
        $actual   = (new Collection($actual))->map(new GetKey())->sort()->values();

        self::assertEquals($expected, $actual);
        self::assertCount(2, $queries);

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

        $models   = (new Collection(array_fill(0, 7, null)))
            ->map(static function (): Type {
                return Type::factory()->create();
            });
        $handler  = Mockery::mock(ExceptionHandler::class);
        $builder  = Type::query()->orderBy((new Type())->getKeyName());
        $iterator = (new ModelsIterator(
            $handler,
            $builder,
            $models->map(new GetKey())->all(),
        ))
            ->onInit(Closure::fromCallable($init))
            ->onFinish(Closure::fromCallable($finish))
            ->onBeforeChunk(Closure::fromCallable($before))
            ->onAfterChunk(Closure::fromCallable($after))
            ->setChunkSize(2)
            ->setOffset(2)
            ->setLimit(3);

        iterator_to_array($iterator);

        $chunkA = static function (array $items) use ($models): bool {
            $expected = $models->slice(2, 2)->map(new GetKey())->sort()->values()->all();
            $actual   = (new Collection($items))->map(new GetKey())->all();

            return $expected === $actual;
        };
        $chunkB = static function (array $items) use ($models): bool {
            $expected = $models->slice(4, 1)->map(new GetKey())->sort()->values()->all();
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

    /**
     * @covers ::count
     */
    public function testCount(): void {
        // Prepare
        $models   = array_fill(0, $this->faker->randomDigit(), 'abc');
        $handler  = Mockery::mock(ExceptionHandler::class);
        $builder  = Type::query();
        $iterator = new ModelsIterator(
            $handler,
            $builder,
            $models,
        );

        self::assertEquals(count($models), count($iterator));
    }
}
