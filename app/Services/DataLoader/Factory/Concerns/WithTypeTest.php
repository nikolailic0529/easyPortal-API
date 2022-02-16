<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Customer;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Factory\DependentModelFactory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithType
 */
class WithTypeTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::type
     */
    public function testType(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(TypeResolver::class);
        $customer   = Customer::factory()->make();
        $type       = TypeModel::factory()->create([
            'object_type' => $customer->getMorphClass(),
        ]);

        $factory = new class($normalizer, $resolver) extends DependentModelFactory {
            use WithType {
                type as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected TypeResolver $types,
            ) {
                // empty
            }

            public function create(Model $object, Type $type): ?Model {
                return null;
            }

            protected function getTypeResolver(): TypeResolver {
                return $this->types;
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        $this->assertEquals($type, $factory->type($customer, $type->key));
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not - it should be created
        $created = $factory->type($customer, ' New  Type ');

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals($customer->getMorphClass(), $created->object_type);
        $this->assertEquals('New Type', $created->key);
        $this->assertEquals('New Type', $created->name);
        $this->assertCount(2, $this->getQueryLog());
    }
}