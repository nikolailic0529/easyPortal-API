<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Customer;
use App\Models\Field;
use App\Services\DataLoader\Factory\DependentModelFactory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\FieldResolver;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithField
 */
class WithFieldTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::field
     */
    public function testField(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(FieldResolver::class);
        $customer   = Customer::factory()->make();
        $field      = Field::factory()->create([
            'object_type' => $customer->getMorphClass(),
        ]);

        $factory = new class($normalizer, $resolver) extends DependentModelFactory {
            use WithField {
                field as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected FieldResolver $fields,
            ) {
                // empty
            }

            public function create(Model $object, Type $type): ?Model {
                return null;
            }

            protected function getFieldResolver(): FieldResolver {
                return $this->fields;
            }
        };

        // If model exists - no action required
        $queries = $this->getQueryLog()->flush();

        self::assertEquals($field, $factory->field($customer, $field->key));
        self::assertCount(1, $queries);

        // If not - it should be created
        $queries = $this->getQueryLog()->flush();
        $created = $factory->field($customer, ' New  field ');

        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals($customer->getMorphClass(), $created->object_type);
        self::assertEquals('New field', $created->key);
        self::assertEquals('New Field', $created->name);
        self::assertCount(2, $queries);
    }
}
