<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use App\Services\DataLoader\Normalizer\Normalizer;
use App\Utils\Cast;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Tests\TestCase;

use function mb_strtoupper;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Cache\Cache
 */
class CacheTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::getByRetriever
     */
    public function testGetByRetriever(): void {
        $items      = $this->items();
        $cache      = $this->cache($items);
        $item       = $this->faker->randomElement($items->all());
        $normalizer = $this->app->make(Normalizer::class);

        self::assertInstanceOf(Model::class, $item);
        self::assertNull($cache->getByRetriever('key', new Key($normalizer, [$this::class])));
        self::assertSame($item, $cache->getByRetriever('key', new Key($normalizer, [$item->getKey()])));
        self::assertSame($item, $cache->getByRetriever('property', new Key($normalizer, [
            $item->getAttribute('property'),
        ])));
        self::assertSame($item, $cache->getByRetriever('property', new Key($normalizer, [
            mb_strtoupper(Cast::toString($item->getAttribute('property'))),
        ])));
    }

    /**
     * @covers ::hasByRetriever
     */
    public function testHasByRetriever(): void {
        $items      = $this->items();
        $cache      = $this->cache($items);
        $item       = $this->faker->randomElement($items->all());
        $normalizer = $this->app->make(Normalizer::class);

        self::assertInstanceOf(Model::class, $item);
        self::assertFalse($cache->hasByRetriever('key', new Key($normalizer, [$this::class])));
        self::assertTrue($cache->hasByRetriever('key', new Key($normalizer, [$item->getKey()])));
        self::assertTrue($cache->hasByRetriever('property', new Key($normalizer, [$item->getAttribute('property')])));
        self::assertTrue($cache->hasByRetriever('property', new Key($normalizer, [
            mb_strtoupper(Cast::toString($item->getAttribute('property'))),
        ])));
    }

    /**
     * @covers ::has
     */
    public function testHas(): void {
        $items      = $this->items();
        $cache      = $this->cache($items);
        $item       = $this->faker->randomElement($items->all());
        $normalizer = $this->app->make(Normalizer::class);

        self::assertInstanceOf(Model::class, $item);
        self::assertFalse($cache->has(new Key($normalizer, [$this::class])));
        self::assertTrue($cache->has(new Key($normalizer, [$item->getKey()])));
        self::assertTrue($cache->has(new Key($normalizer, [$item->getAttribute('property')])));
        self::assertTrue($cache->has(new Key($normalizer, [
            mb_strtoupper(Cast::toString($item->getAttribute('property'))),
        ])));
    }

    /**
     * @covers ::get
     */
    public function testGet(): void {
        $items      = $this->items();
        $cache      = $this->cache($items);
        $item       = $this->faker->randomElement($items->all());
        $normalizer = $this->app->make(Normalizer::class);

        self::assertInstanceOf(Model::class, $item);
        self::assertNull($cache->get(new Key($normalizer, [$this::class])));
        self::assertSame($item, $cache->get(new Key($normalizer, [$item->getKey()])));
        self::assertSame($item, $cache->get(new Key($normalizer, [$item->getAttribute('property')])));
        self::assertSame($item, $cache->get(new Key($normalizer, [
            mb_strtoupper(Cast::toString($item->getAttribute('property'))),
        ])));
    }

    /**
     * @covers ::putNull
     */
    public function testPutNull(): void {
        $items       = $this->items();
        $cache       = $this->cache($items);
        $normalizer  = $this->app->make(Normalizer::class);
        $item        = $this->faker->randomElement($items->all());
        $itemKey     = new Key($normalizer, [$item->getKey()]);
        $propertyKey = new Key($normalizer, [$item->getAttribute('property')]);

        self::assertSame($item, $cache->get($itemKey));
        self::assertTrue($cache->has($itemKey));
        self::assertSame($item, $cache->get($propertyKey));
        self::assertTrue($cache->has($propertyKey));

        $cache->putNull($itemKey);

        self::assertNull($cache->get($itemKey));
        self::assertTrue($cache->has($itemKey));
        self::assertSame($item, $cache->get($propertyKey));
        self::assertTrue($cache->has($propertyKey));
    }

    /**
     * @covers ::putNull
     * @covers ::hasNull
     */
    public function testPutNulls(): void {
        $items      = $this->items();
        $cache      = $this->cache($items);
        $normalizer = $this->app->make(Normalizer::class);
        $itemA      = $items->first();
        $keyA       = new Key($normalizer, [$itemA?->getKey()]);
        $itemB      = $items->last();
        $keyB       = new Key($normalizer, [$itemB?->getKey()]);

        self::assertFalse($cache->hasNull($keyA));
        self::assertFalse($cache->hasNull($keyB));

        $cache->putNulls([$keyA, $keyB]);

        self::assertTrue($cache->hasNull($keyA));
        self::assertTrue($cache->hasNull($keyB));
    }

    /**
     * @covers ::put
     */
    public function testPut(): void {
        $items       = $this->items();
        $cache       = $this->cache($items);
        $normalizer  = $this->app->make(Normalizer::class);
        $item        = $this->item();
        $itemKey     = new Key($normalizer, [$item->getKey()]);
        $propertyKey = new Key($normalizer, [$item->getAttribute('property')]);

        self::assertFalse($cache->has($itemKey));
        self::assertFalse($cache->has($propertyKey));

        self::assertSame($item, $cache->put($item));

        self::assertTrue($cache->has($itemKey));
        self::assertTrue($cache->has($propertyKey));
    }

    /**
     * @covers ::put
     */
    public function testPutAfterNull(): void {
        $items       = $this->items();
        $cache       = $this->cache($items);
        $normalizer  = $this->app->make(Normalizer::class);
        $item        = $this->item();
        $itemKey     = new Key($normalizer, [$item->getKey()]);
        $propertyKey = new Key($normalizer, [$item->getAttribute('property')]);

        self::assertFalse($cache->has($itemKey));
        self::assertFalse($cache->has($propertyKey));

        $cache->putNull($itemKey);

        self::assertNull($cache->get($itemKey));
        self::assertNull($cache->get($propertyKey));

        $cache->put($item);

        self::assertSame($item, $cache->get($itemKey));
        self::assertSame($item, $cache->get($propertyKey));
    }

    /**
     * @covers ::putAll
     */
    public function testPutAll(): void {
        $items      = $this->items();
        $cache      = $this->cache($items);
        $normalizer = $this->app->make(Normalizer::class);
        $itemA      = $this->item();
        $keyA       = new Key($normalizer, [$itemA->getKey()]);
        $itemB      = $this->item();
        $keyB       = new Key($normalizer, [$itemB->getKey()]);

        self::assertNotEquals($keyA, $keyB);
        self::assertFalse($cache->has($keyA));
        self::assertFalse($cache->has($keyB));

        $cache->putAll(new Collection([$itemA, $itemB]));

        self::assertTrue($cache->has($keyA));
        self::assertTrue($cache->has($keyB));
    }

    /**
     * @covers ::reset
     */
    public function testReset(): void {
        $items      = $this->items();
        $cache      = $this->cache($items);
        $normalizer = $this->app->make(Normalizer::class);
        $itemA      = $this->item();
        $keyA       = new Key($normalizer, [$itemA->getKey()]);
        $itemB      = $this->item();
        $keyB       = new Key($normalizer, [$itemB->getKey()]);

        $cache->putAll(new Collection([$itemA, $itemB]));

        self::assertTrue($cache->has($keyA));
        self::assertTrue($cache->has($keyB));

        $cache->reset();

        self::assertFalse($cache->has($keyA));
        self::assertFalse($cache->has($keyB));
    }

    /**
     * @covers ::getAll
     */
    public function testGetAll(): void {
        $items      = $this->items();
        $cache      = $this->cache($items);
        $normalizer = $this->app->make(Normalizer::class);
        $item       = $this->item();
        $key        = new Key($normalizer, [$item->getKey()]);

        $cache->putNull($key);

        self::assertEquals($items, $cache->getAll());
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    protected function item(): Model {
        $model = new class() extends Model {
            // empty
        };

        $model->setAttribute($model->getKeyName(), $this->faker->randomNumber());
        $model->setAttribute('property', $this->faker->uuid());

        return $model;
    }

    /**
     * @return EloquentCollection<int, Model>
     */
    protected function items(): EloquentCollection {
        return new EloquentCollection([
            $this->item(),
            $this->item(),
        ]);
    }

    /**
     * @param Collection<int, Model> $items
     *
     * @return Cache<Model>
     */
    protected function cache(Collection $items): Cache {
        $normalizer = $this->app->make(Normalizer::class);
        $cache      = new Cache([
            'key'      => new class($normalizer) implements KeyRetriever {
                public function __construct(
                    protected Normalizer $normalizer,
                ) {
                    // empty
                }

                public function getKey(Model $model): Key {
                    return new Key($this->normalizer, [$model->getKeyName() => $model->getKey()]);
                }
            },
            'property' => new class($normalizer) implements KeyRetriever {
                public function __construct(
                    protected Normalizer $normalizer,
                ) {
                    // empty
                }

                public function getKey(Model $model): Key {
                    return new Key($this->normalizer, ['property' => $model->getAttribute('property')]);
                }
            },
        ]);

        return $cache->putAll($items);
    }
    // </editor-fold>
}
