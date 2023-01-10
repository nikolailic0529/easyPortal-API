<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Customer;
use App\Models\Data\Status as StatusModel;
use App\Services\DataLoader\Factory\Factory;
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
        $resolver = $this->app->make(StatusResolver::class);
        $customer = Customer::factory()->make();
        $status   = StatusModel::factory()->create([
            'object_type' => $customer->getMorphClass(),
        ]);

        $factory = new class($resolver) extends Factory {
            use WithStatus {
                status as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected StatusResolver $statusResolver,
            ) {
                // empty
            }

            protected function getStatusResolver(): StatusResolver {
                return $this->statusResolver;
            }

            public function create(Type $type, bool $force = false): ?Model {
                return null;
            }

            public function getModel(): string {
                return Model::class;
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
