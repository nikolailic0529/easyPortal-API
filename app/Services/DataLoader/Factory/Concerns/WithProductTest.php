<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Product;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Resolver\Resolvers\ProductResolver;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Factory\Concerns\WithProduct
 */
class WithProductTest extends TestCase {
    use WithQueryLog;

    public function testProduct(): void {
        // Prepare
        $resolver = $this->app->make(ProductResolver::class);
        $product  = Product::factory()->create();
        $oem      = $product->oem;

        $factory = new class($resolver) extends Factory {
            use WithProduct {
                product as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ProductResolver $productResolver,
            ) {
                // empty
            }

            public function getModel(): string {
                return Model::class;
            }

            public function create(Type $type, bool $force = false): ?Model {
                return null;
            }

            protected function getProductResolver(): ProductResolver {
                return $this->productResolver;
            }
        };

        // If model exists and not changed - no action required
        $queries = $this->getQueryLog()->flush();

        self::assertEquals(
            $product->withoutRelations(),
            $factory->product(
                $oem,
                $product->sku,
                $product->name,
                $product->eol,
                $product->eos,
            )->withoutRelations(),
        );
        self::assertCount(1, $queries);

        // If model exists and changed - it should be updated except `name`
        $queries = $this->getQueryLog()->flush();
        $newEos  = ($this->faker->boolean() ? Date::now() : null)?->startOfDay();
        $newEol  = Date::now()->startOfDay();
        $newName = $this->faker->sentence();
        $updated = $factory->product(
            $oem,
            $product->sku,
            $newName,
            $newEol,
            $newEos,
        );

        self::assertEquals($product->name, $updated->name);
        self::assertEquals($newEol, $updated->eol);
        self::assertEquals($newEos, $updated->eos);

        self::assertCount(1, $queries);

        // If model exists and changed - empty `name` should be updated
        $product       = $updated;
        $product->name = '';
        $product->save();

        $queries = $this->getQueryLog()->flush();
        $newName = $this->faker->sentence();
        $updated = $factory->product(
            $oem,
            $product->sku,
            $newName,
            $product->eol,
            $product->eos,
        );

        self::assertEquals($newName, $updated->name);
        self::assertCount(1, $queries);

        // If not - it should be created
        $sku     = $this->faker->uuid();
        $name    = $this->faker->sentence();
        $created = $factory->product(
            $oem,
            $sku,
            $name,
            null,
            null,
        );

        self::assertEquals($oem->getKey(), $created->oem_id);
        self::assertEquals($sku, $created->sku);
        self::assertEquals($name, $created->name);
    }
}
