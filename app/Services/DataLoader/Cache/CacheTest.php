<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use App\Models\Model;
use App\Services\DataLoader\Normalizer;
use Illuminate\Database\Eloquent\Model as EloquentModel;
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

        $this->assertNull($cache->getByRetriever('key', new Key($normalizer, [$this::class])));
        $this->assertSame($item, $cache->getByRetriever('key', new Key($normalizer, [$item->getKey()])));
        $this->assertSame($item, $cache->getByRetriever('property', new Key($normalizer, [$item->property])));
        $this->assertSame($item, $cache->getByRetriever('property', new Key($normalizer, [
            mb_strtoupper($item->property),
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

        $this->assertFalse($cache->hasByRetriever('key', new Key($normalizer, [$this::class])));
        $this->assertTrue($cache->hasByRetriever('key', new Key($normalizer, [$item->getKey()])));
        $this->assertTrue($cache->hasByRetriever('property', new Key($normalizer, [$item->property])));
        $this->assertTrue($cache->hasByRetriever('property', new Key($normalizer, [
            mb_strtoupper($item->property),
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

        $this->assertFalse($cache->has(new Key($normalizer, [$this::class])));
        $this->assertTrue($cache->has(new Key($normalizer, [$item->getKey()])));
        $this->assertTrue($cache->has(new Key($normalizer, [$item->property])));
        $this->assertTrue($cache->has(new Key($normalizer, [
            mb_strtoupper($item->property),
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

        $this->assertNull($cache->get(new Key($normalizer, [$this::class])));
        $this->assertSame($item, $cache->get(new Key($normalizer, [$item->getKey()])));
        $this->assertSame($item, $cache->get(new Key($normalizer, [$item->property])));
        $this->assertSame($item, $cache->get(new Key($normalizer, [
            mb_strtoupper($item->property),
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
        $propertyKey = new Key($normalizer, [$item->property]);

        $this->assertSame($item, $cache->get($itemKey));
        $this->assertTrue($cache->has($itemKey));
        $this->assertSame($item, $cache->get($propertyKey));
        $this->assertTrue($cache->has($propertyKey));

        $cache->putNull($itemKey);

        $this->assertNull($cache->get($itemKey));
        $this->assertTrue($cache->has($itemKey));
        $this->assertSame($item, $cache->get($propertyKey));
        $this->assertTrue($cache->has($propertyKey));
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
        $keyA       = new Key($normalizer, [$itemA->getKey()]);
        $itemB      = $items->last();
        $keyB       = new Key($normalizer, [$itemB->getKey()]);

        $this->assertFalse($cache->hasNull($keyA));
        $this->assertFalse($cache->hasNull($keyB));

        $cache->putNulls([$keyA, $keyB]);

        $this->assertTrue($cache->hasNull($keyA));
        $this->assertTrue($cache->hasNull($keyB));
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
        $propertyKey = new Key($normalizer, [$item->property]);

        $this->assertFalse($cache->has($itemKey));
        $this->assertFalse($cache->has($propertyKey));

        $this->assertSame($item, $cache->put($item));

        $this->assertTrue($cache->has($itemKey));
        $this->assertTrue($cache->has($propertyKey));
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
        $propertyKey = new Key($normalizer, [$item->property]);

        $this->assertFalse($cache->has($itemKey));
        $this->assertFalse($cache->has($propertyKey));

        $cache->putNull($itemKey);

        $this->assertNull($cache->get($itemKey));
        $this->assertNull($cache->get($propertyKey));

        $cache->put($item);

        $this->assertSame($item, $cache->get($itemKey));
        $this->assertSame($item, $cache->get($propertyKey));
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

        $this->assertFalse($cache->has($keyA));
        $this->assertFalse($cache->has($keyB));

        $cache->putAll(new Collection([$itemA, $itemB]));

        $this->assertTrue($cache->has($keyA));
        $this->assertTrue($cache->has($keyB));
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

        $this->assertTrue($cache->has($keyA));
        $this->assertTrue($cache->has($keyB));

        $cache->reset();

        $this->assertFalse($cache->has($keyA));
        $this->assertFalse($cache->has($keyB));
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

        $this->assertEquals($items, $cache->getAll());
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    protected function item(): Model {
        return new class($this->faker->uuid, $this->faker->uuid) extends Model {
            public function __construct(string $key, string $property) {
                parent::__construct();

                $this->{$this->getKeyName()} = $key;
                $this->property              = $property;
            }
        };
    }

    protected function items(): Collection {
        return new Collection([
            $this->item(),
            $this->item(),
        ]);
    }

    protected function cache(Collection $items): Cache {
        $normalizer = $this->app->make(Normalizer::class);

        return new Cache($items, [
            'key'      => new class($normalizer) implements KeyRetriever {
                public function __construct(
                    protected Normalizer $normalizer,
                ) {
                    // empty
                }

                public function getKey(EloquentModel $model): Key {
                    return new Key($this->normalizer, [$model->getKeyName() => $model->getKey()]);
                }
            },
            'property' => new class($normalizer) implements KeyRetriever {
                public function __construct(
                    protected Normalizer $normalizer,
                ) {
                    // empty
                }

                public function getKey(EloquentModel $model): Key {
                    return new Key($this->normalizer, ['property' => $model->property]);
                }
            },
        ]);
    }
    // </editor-fold>
}
