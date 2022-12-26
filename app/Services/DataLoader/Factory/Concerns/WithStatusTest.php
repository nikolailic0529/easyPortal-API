<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Customer;
use App\Models\Data\Status as StatusModel;
use App\Services\DataLoader\Factory\DependentModelFactory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\StatusResolver;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithStatus
 */
class WithStatusTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::status
     */
    public function testStatus(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(StatusResolver::class);
        $customer   = Customer::factory()->make();
        $status     = StatusModel::factory()->create([
            'object_type' => $customer->getMorphClass(),
        ]);

        $factory = new class($normalizer, $resolver) extends DependentModelFactory {
            use WithStatus {
                status as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected  StatusResolver $statusResolver,
            ) {
                // empty
            }

            protected function getStatusResolver(): StatusResolver {
                return $this->statusResolver;
            }

            public function create(Model $object, Type $type): ?Model {
                return null;
            }
        };

        // If model exists - no action required
        $queries = $this->getQueryLog()->flush();

        self::assertEquals($status, $factory->status($customer, $status->key));
        self::assertCount(1, $queries);

        // If not - it should be created
        $queries = $this->getQueryLog()->flush();
        $created = $factory->status($customer, 'New status');

        self::assertNotNull($created);
        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals($customer->getMorphClass(), $created->object_type);
        self::assertEquals('New status', $created->key);
        self::assertEquals('New Status', $created->name);
        self::assertCount(2, $queries);
    }
}
