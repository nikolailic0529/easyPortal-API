<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

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
        $items = $this->items();
        $cache = $this->cache($items);
        $item  = $this->faker->randomElement($items->all());

        self::assertInstanceOf(Model::class, $item);
        self::assertNull($cache->getByRetriever('key', new Key([$this::class])));
        self::assertSame($item, $cache->getByRetriever('key', new Key([$item->getKey()])));
        self::assertSame($item, $cache->getByRetriever('property', new Key([
            $item->getAttribute('property'),
        ])));
        self::assertSame($item, $cache->getByRetriever('property', new Key([
            mb_strtoupper(Cast::toString($item->getAttribute('property'))),
        ])));
    }

    /**
     * @covers ::hasByRetriever
     */
    public function testHasByRetriever(): void {
        $items = $this->items();
        $cache = $this->cache($items);
        $item  = $this->faker->randomElement($items->all());

        self::assertInstanceOf(Model::class, $item);
        self::assertFalse($cache->hasByRetriever('key', new Key([$this::class])));
        self::assertTrue($cache->hasByRetriever('key', new Key([$item->getKey()])));
        self::assertTrue($cache->hasByRetriever('property', new Key([$item->getAttribute('property')])));
        self::assertTrue($cache->hasByRetriever('property', new Key([
            mb_strtoupper(Cast::toString($item->getAttribute('property'))),
        ])));
    }

    /**
     * @covers ::has
     */
    public function testHas(): void {
        $items = $this->items();
        $cache = $this->cache($items);
        $item  = $this->faker->randomElement($items->all());

        self::assertInstanceOf(Model::class, $item);
        self::assertFalse($cache->has(new Key([$this::class])));
        self::assertTrue($cache->has(new Key([$item->getKey()])));
        self::assertTrue($cache->has(new Key([$item->getAttribute('property')])));
        self::assertTrue($cache->has(new Key([
            mb_strtoupper(Cast::toString($item->getAttribute('property'))),
        ])));
    }

    /**
     * @covers ::get
     */
    public function testGet(): void {
        $items = $this->items();
        $cache = $this->cache($items);
        $item  = $this->faker->randomElement($items->all());

        self::assertInstanceOf(Model::class, $item);
        self::assertNull($cache->get(new Key([$this::class])));
        self::assertSame($item, $cache->get(new Key([$item->getKey()])));
        self::assertSame($item, $cache->get(new Key([$item->getAttribute('property')])));
        self::assertSame($item, $cache->get(new Key([
            mb_strtoupper(Cast::toString($item->getAttribute('property'))),
        ])));
    }

    /**
     * @covers ::putNull
     */
    public function testPutNull(): void {
        $items       = $this->items();
        $cache       = $this->cache($items);
        $item        = $this->faker->randomElement($items->all());
        $itemKey     = new Key([$item->getKey()]);
        $propertyKey = new Key([$item->getAttribute('property')]);

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
        $items = $this->items();
        $cache = $this->cache($items);
        $itemA = $items->first();
        $keyA  = new Key([$itemA?->getKey()]);
        $itemB = $items->last();
        $keyB  = new Key([$itemB?->getKey()]);

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
        $item        = $this->item();
        $itemKey     = new Key([$item->getKey()]);
        $propertyKey = new Key([$item->getAttribute('property')]);

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
        $item        = $this->item();
        $itemKey     = new Key([$item->getKey()]);
        $propertyKey = new Key([$item->getAttribute('property')]);

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
        $items = $this->items();
        $cache = $this->cache($items);
        $itemA = $this->item();
        $keyA  = new Key([$itemA->getKey()]);
        $itemB = $this->item();
        $keyB  = new Key([$itemB->getKey()]);

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
        $items = $this->items();
        $cache = $this->cache($items);
        $itemA = $this->item();
        $keyA  = new Key([$itemA->getKey()]);
        $itemB = $this->item();
        $keyB  = new Key([$itemB->getKey()]);

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
        $items = $this->items();
        $cache = $this->cache($items);
        $item  = $this->item();
        $key   = new Key([$item->getKey()]);

        $cache->putNull($key);

        self::assertEquals($items, $cache->getAll());
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    protected function item(): Model {
        $model = new class() extends Model {
            /**
             * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
             *
             * @var string
             */
            protected $keyType = 'string';
        };

        $model->setAttribute($model->getKeyName(), $this->faker->uuid());
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
        $cache = new Cache([
            'key'      => new class() implements KeyRetriever {
                public function getKey(Model $model): Key {
                    return new Key([$model->getKeyName() => $model->getKey()]);
                }
            },
            'property' => new class() implements KeyRetriever {
                public function getKey(Model $model): Key {
                    return new Key(['property' => $model->getAttribute('property')]);
                }
            },
        ]);

        return $cache->putAll($items);
    }
    // </editor-fold>
}
