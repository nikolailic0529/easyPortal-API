<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Customer;
use App\Models\Status as StatusModel;
use App\Services\DataLoader\Factories\DependentModelFactory;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\StatusResolver;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\Concerns\WithStatus
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

        $this->flushQueryLog();

        // If model exists - no action required
        $this->assertEquals($status, $factory->status($customer, $status->key));
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not - it should be created
        $created = $factory->status($customer, ' New  Status ');

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals($customer->getMorphClass(), $created->object_type);
        $this->assertEquals('New Status', $created->key);
        $this->assertEquals('New Status', $created->name);
        $this->assertCount(2, $this->getQueryLog());
    }
}
