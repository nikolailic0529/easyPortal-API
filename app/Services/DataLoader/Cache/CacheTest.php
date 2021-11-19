<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use App\Utils\Eloquent\Model;
use App\Services\DataLoader\Normalizer;
use Illuminate\Support\Collection;
use Tests\TestCase;

use function mb_strtoupper;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Cache\Cache
 */
class CacheTest extends TestCase {
    protected ?Cache      $cache;
    protected ?Collection $items;

    protected function setUp(): void {
        parent::setUp();

        $normalizer  = $this->app->make(Normalizer::class);
        $this->items = new Collection([
            $this->item(),
            $this->item(),
        ]);
        $this->cache = new Cache($this->items, [
            'key'      => new class($normalizer) implements KeyRetriever {
                public function __construct(
                    protected Normalizer $normalizer,
                ) {
                    // empty
                }

                public function get(Model $model): Key {
                    return new Key($this->normalizer, [$model->getKey()]);
                }
            },
            'property' => new class($normalizer) implements KeyRetriever {
                public function __construct(
                    protected Normalizer $normalizer,
                ) {
                    // empty
                }

                public function get(Model $model): Key {
                    return new Key($this->normalizer, [$model->property]);
                }
            },
        ]);
    }

    protected function tearDown(): void {
        $this->items = null;
        $this->cache = null;

        parent::tearDown();
    }

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::getByRetriever
     */
    public function testGetByRetriever(): void {
        $item       = $this->faker->randomElement($this->items->all());
        $normalizer = $this->app->make(Normalizer::class);

        $this->assertNull($this->cache->getByRetriever('key', new Key($normalizer, [$this::class])));
        $this->assertSame($item, $this->cache->getByRetriever('key', new Key($normalizer, [$item->getKey()])));
        $this->assertSame($item, $this->cache->getByRetriever('property', new Key($normalizer, [$item->property])));
        $this->assertSame($item, $this->cache->getByRetriever('property', new Key($normalizer, [
            mb_strtoupper($item->property),
        ])));
    }

    /**
     * @covers ::hasByRetriever
     */
    public function testHasByRetriever(): void {
        $item       = $this->faker->randomElement($this->items->all());
        $normalizer = $this->app->make(Normalizer::class);

        $this->assertFalse($this->cache->hasByRetriever('key', new Key($normalizer, [$this::class])));
        $this->assertTrue($this->cache->hasByRetriever('key', new Key($normalizer, [$item->getKey()])));
        $this->assertTrue($this->cache->hasByRetriever('property', new Key($normalizer, [$item->property])));
        $this->assertTrue($this->cache->hasByRetriever('property', new Key($normalizer, [
            mb_strtoupper($item->property),
        ])));
    }

    /**
     * @covers ::has
     */
    public function testHas(): void {
        $item       = $this->faker->randomElement($this->items->all());
        $normalizer = $this->app->make(Normalizer::class);

        $this->assertFalse($this->cache->has(new Key($normalizer, [$this::class])));
        $this->assertTrue($this->cache->has(new Key($normalizer, [$item->getKey()])));
        $this->assertTrue($this->cache->has(new Key($normalizer, [$item->property])));
        $this->assertTrue($this->cache->has(new Key($normalizer, [
            mb_strtoupper($item->property),
        ])));
    }

    /**
     * @covers ::get
     */
    public function testGet(): void {
        $item       = $this->faker->randomElement($this->items->all());
        $normalizer = $this->app->make(Normalizer::class);

        $this->assertNull($this->cache->get(new Key($normalizer, [$this::class])));
        $this->assertSame($item, $this->cache->get(new Key($normalizer, [$item->getKey()])));
        $this->assertSame($item, $this->cache->get(new Key($normalizer, [$item->property])));
        $this->assertSame($item, $this->cache->get(new Key($normalizer, [
            mb_strtoupper($item->property),
        ])));
    }

    /**
     * @covers ::putNull
     */
    public function testPutNull(): void {
        $normalizer  = $this->app->make(Normalizer::class);
        $item        = $this->faker->randomElement($this->items->all());
        $itemKey     = new Key($normalizer, [$item->getKey()]);
        $propertyKey = new Key($normalizer, [$item->property]);

        $this->assertSame($item, $this->cache->get($itemKey));
        $this->assertTrue($this->cache->has($itemKey));
        $this->assertSame($item, $this->cache->get($propertyKey));
        $this->assertTrue($this->cache->has($propertyKey));

        $this->cache->putNull($itemKey);

        $this->assertNull($this->cache->get($itemKey));
        $this->assertTrue($this->cache->has($itemKey));
        $this->assertSame($item, $this->cache->get($propertyKey));
        $this->assertTrue($this->cache->has($propertyKey));
    }

    /**
     * @covers ::putNull
     * @covers ::hasNull
     */
    public function testPutNulls(): void {
        $normalizer = $this->app->make(Normalizer::class);
        $itemA      = $this->items->first();
        $keyA       = new Key($normalizer, [$itemA->getKey()]);
        $itemB      = $this->items->last();
        $keyB       = new Key($normalizer, [$itemB->getKey()]);

        $this->assertFalse($this->cache->hasNull($keyA));
        $this->assertFalse($this->cache->hasNull($keyB));

        $this->cache->putNulls([$keyA, $keyB]);

        $this->assertTrue($this->cache->hasNull($keyA));
        $this->assertTrue($this->cache->hasNull($keyB));
    }

    /**
     * @covers ::put
     */
    public function testPut(): void {
        $normalizer  = $this->app->make(Normalizer::class);
        $item        = $this->item();
        $itemKey     = new Key($normalizer, [$item->getKey()]);
        $propertyKey = new Key($normalizer, [$item->property]);

        $this->assertFalse($this->cache->has($itemKey));
        $this->assertFalse($this->cache->has($propertyKey));

        $this->assertSame($item, $this->cache->put($item));

        $this->assertTrue($this->cache->has($itemKey));
        $this->assertTrue($this->cache->has($propertyKey));
    }

    /**
     * @covers ::put
     */
    public function testPutAfterNull(): void {
        $normalizer  = $this->app->make(Normalizer::class);
        $item        = $this->item();
        $itemKey     = new Key($normalizer, [$item->getKey()]);
        $propertyKey = new Key($normalizer, [$item->property]);

        $this->assertFalse($this->cache->has($itemKey));
        $this->assertFalse($this->cache->has($propertyKey));

        $this->cache->putNull($itemKey);

        $this->assertNull($this->cache->get($itemKey));
        $this->assertNull($this->cache->get($propertyKey));

        $this->cache->put($item);

        $this->assertSame($item, $this->cache->get($itemKey));
        $this->assertSame($item, $this->cache->get($propertyKey));
    }

    /**
     * @covers ::putAll
     */
    public function testPutAll(): void {
        $normalizer = $this->app->make(Normalizer::class);
        $itemA      = $this->item();
        $keyA       = new Key($normalizer, [$itemA->getKey()]);
        $itemB      = $this->item();
        $keyB       = new Key($normalizer, [$itemB->getKey()]);

        $this->assertFalse($this->cache->has($keyA));
        $this->assertFalse($this->cache->has($keyB));

        $this->cache->putAll(new Collection([$itemA, $itemB]));

        $this->assertTrue($this->cache->has($keyA));
        $this->assertTrue($this->cache->has($keyB));
    }

    /**
     * @covers ::reset
     */
    public function testReset(): void {
        $normalizer = $this->app->make(Normalizer::class);
        $itemA      = $this->item();
        $keyA       = new Key($normalizer, [$itemA->getKey()]);
        $itemB      = $this->item();
        $keyB       = new Key($normalizer, [$itemB->getKey()]);

        $this->cache->putAll(new Collection([$itemA, $itemB]));

        $this->assertTrue($this->cache->has($keyA));
        $this->assertTrue($this->cache->has($keyB));

        $this->cache->reset();

        $this->assertFalse($this->cache->has($keyA));
        $this->assertFalse($this->cache->has($keyB));
    }

    /**
     * @covers ::getAll
     */
    public function testGetAll(): void {
        $normalizer = $this->app->make(Normalizer::class);
        $item       = $this->item();
        $key        = new Key($normalizer, [$item->getKey()]);

        $this->cache->putNull($key);

        $this->assertEquals($this->items, $this->cache->getAll());
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    protected function item(): Model {
        return new class($this->faker->uuid, $this->faker->word) extends Model {
            public function __construct(string $key, string $property) {
                parent::__construct();

                $this->{$this->getKeyName()} = $key;
                $this->property              = $property;
            }
        };
    }
    // </editor-fold>
}
