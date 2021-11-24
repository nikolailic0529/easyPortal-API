<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Product;
use App\Services\DataLoader\Factories\ModelFactory;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\ProductResolver;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\Concerns\WithProduct
 */
class WithProductTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::product
     */
    public function testProduct(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(ProductResolver::class);
        $product    = Product::factory()->create();
        $oem        = $product->oem;

        $factory = new class($normalizer, $resolver) extends ModelFactory {
            use WithProduct {
                product as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ProductResolver $productResolver,
            ) {
                // empty
            }

            public function create(Type $type): ?Model {
                return null;
            }

            protected function getProductResolver(): ProductResolver {
                return $this->productResolver;
            }
        };

        $this->flushQueryLog();

        // If model exists and not changed - no action required
        $this->assertEquals(
            $product->withoutRelations(),
            $factory->product(
                $oem,
                $product->sku,
                $product->name,
                "{$product->eol->getTimestamp()}000",
                "{$product->eos->getTimestamp()}000",
            )->withoutRelations(),
        );
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If model exists and changed - it should be updated
        $newEos  = $this->faker->randomElement(['', null]);
        $newEol  = Date::now();
        $newName = $this->faker->sentence;
        $updated = $factory->product(
            $oem,
            $product->sku,
            $newName,
            "{$newEol->getTimestamp()}000",
            $newEos,
        );

        $this->assertEquals($product->name, $updated->name);
        $this->assertEquals($newEol, $newEol);
        $this->assertNull($updated->eos);

        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not - it should be created
        $sku     = $this->faker->uuid;
        $name    = $this->faker->sentence;
        $created = $factory->product(
            $oem,
            $sku,
            $name,
            null,
            null,
        );

        $this->assertNotNull($created);
        $this->assertEquals($oem->getKey(), $created->oem_id);
        $this->assertEquals($sku, $created->sku);
        $this->assertEquals($name, $created->name);

        $this->flushQueryLog();
    }
}
