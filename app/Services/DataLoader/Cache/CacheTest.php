<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use App\Models\Model;
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

        $this->items = new Collection([
            $this->item(),
            $this->item(),
        ]);
        $this->cache = new Cache($this->items, [
            'key'      => new class() implements KeyRetriever {
                public function get(Model $model): mixed {
                    return $model->getKey();
                }
            },
            'property' => new class() implements KeyRetriever {
                public function get(Model $model): mixed {
                    return $model->property;
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
        $item = $this->faker->randomElement($this->items->all());

        $this->assertNull($this->cache->getByRetriever('key', $this::class));
        $this->assertSame($item, $this->cache->getByRetriever('key', $item->getKey()));
        $this->assertSame($item, $this->cache->getByRetriever('property', $item->property));
        $this->assertSame($item, $this->cache->getByRetriever('property', mb_strtoupper($item->property)));
    }

    /**
     * @covers ::hasByRetriever
     */
    public function testHasByRetriever(): void {
        $item = $this->faker->randomElement($this->items->all());

        $this->assertFalse($this->cache->hasByRetriever('key', $this::class));
        $this->assertTrue($this->cache->hasByRetriever('key', $item->getKey()));
        $this->assertTrue($this->cache->hasByRetriever('property', $item->property));
        $this->assertTrue($this->cache->hasByRetriever('property', mb_strtoupper($item->property)));
    }

    /**
     * @covers ::has
     */
    public function testHas(): void {
        $item = $this->faker->randomElement($this->items->all());

        $this->assertFalse($this->cache->has($this::class));
        $this->assertTrue($this->cache->has($item->getKey()));
        $this->assertTrue($this->cache->has($item->property));
        $this->assertTrue($this->cache->has(mb_strtoupper($item->property)));
    }

    /**
     * @covers ::get
     */
    public function testGet(): void {
        $item = $this->faker->randomElement($this->items->all());

        $this->assertNull($this->cache->get($this::class));
        $this->assertSame($item, $this->cache->get($item->getKey()));
        $this->assertSame($item, $this->cache->get($item->property));
        $this->assertSame($item, $this->cache->get(mb_strtoupper($item->property)));
    }

    /**
     * @covers ::putNull
     */
    public function testPutNull(): void {
        $item = $this->faker->randomElement($this->items->all());

        $this->assertSame($item, $this->cache->get($item->getKey()));
        $this->assertTrue($this->cache->has($item->getKey()));
        $this->assertSame($item, $this->cache->get($item->property));
        $this->assertTrue($this->cache->has($item->property));

        $this->cache->putNull($item->getKey());

        $this->assertNull($this->cache->get($item->getKey()));
        $this->assertTrue($this->cache->has($item->getKey()));
        $this->assertSame($item, $this->cache->get($item->property));
        $this->assertTrue($this->cache->has($item->property));
    }

    /**
     * @covers ::putNull
     * @covers ::hasNull
     */
    public function testPutNulls(): void {
        $a = $this->items->first();
        $b = $this->items->last();

        $this->assertFalse($this->cache->hasNull($a->getKey()));
        $this->assertFalse($this->cache->hasNull($b->getKey()));

        $this->cache->putNulls([$a->getKey(), $b->getKey()]);

        $this->assertTrue($this->cache->hasNull($a->getKey()));
        $this->assertTrue($this->cache->hasNull($b->getKey()));
    }

    /**
     * @covers ::put
     */
    public function testPut(): void {
        $item = $this->item();

        $this->assertFalse($this->cache->has($item->getKey()));
        $this->assertFalse($this->cache->has($item->property));

        $this->assertSame($item, $this->cache->put($item));

        $this->assertTrue($this->cache->has($item->getKey()));
        $this->assertTrue($this->cache->has($item->property));
    }

    /**
     * @covers ::put
     */
    public function testPutAfterNull(): void {
        $item = $this->item();

        $this->assertFalse($this->cache->has($item->getKey()));
        $this->assertFalse($this->cache->has($item->property));

        $this->cache->putNull($item->getKey());

        $this->assertNull($this->cache->get($item->getKey()));
        $this->assertNull($this->cache->get($item->property));

        $this->cache->put($item);

        $this->assertSame($item, $this->cache->get($item->getKey()));
        $this->assertSame($item, $this->cache->get($item->property));
    }

    /**
     * @covers ::putAll
     */
    public function testPutAll(): void {
        $a = $this->item();
        $b = $this->item();

        $this->assertFalse($this->cache->has($a->getKey()));
        $this->assertFalse($this->cache->has($b->getKey()));

        $this->cache->putAll(new Collection([$a, $b]));

        $this->assertTrue($this->cache->has($a->getKey()));
        $this->assertTrue($this->cache->has($b->getKey()));
    }

    /**
     * @covers ::reset
     */
    public function testReset(): void {
        $a = $this->item();
        $b = $this->item();

        $this->cache->putAll(new Collection([$a, $b]));

        $this->assertTrue($this->cache->has($a->getKey()));
        $this->assertTrue($this->cache->has($b->getKey()));

        $this->cache->reset();

        $this->assertFalse($this->cache->has($a->getKey()));
        $this->assertFalse($this->cache->has($b->getKey()));
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
