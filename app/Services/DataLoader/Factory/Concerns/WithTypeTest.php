<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Customer;
use App\Models\Data\Type as TypeModel;
use App\Services\DataLoader\Factory\DependentModelFactory;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
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
        $resolver = $this->app->make(TypeResolver::class);
        $customer = Customer::factory()->make();
        $type     = TypeModel::factory()->create([
            'object_type' => $customer->getMorphClass(),
        ]);

        $factory = new class($resolver) extends DependentModelFactory {
            use WithType {
                type as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
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

        // If model exists - no action required
        $queries = $this->getQueryLog()->flush();

        self::assertEquals($type, $factory->type($customer, $type->key));
        self::assertCount(1, $queries);

        // If not - it should be created
        $queries = $this->getQueryLog()->flush();
        $created = $factory->type($customer, 'New type');

        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals($customer->getMorphClass(), $created->object_type);
        self::assertEquals('New type', $created->key);
        self::assertEquals('New Type', $created->name);
        self::assertCount(2, $queries);
    }
}
