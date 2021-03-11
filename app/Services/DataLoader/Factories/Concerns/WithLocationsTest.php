<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Customer;
use App\Models\Location as LocationModel;
use App\Models\Model;
use App\Services\DataLoader\Factories\LocationFactory;
use App\Services\DataLoader\Factories\ModelFactory;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Location;
use App\Services\DataLoader\Schema\Type;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

use function reset;
use function tap;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\Concerns\WithLocations
 */
class WithLocationsTest extends TestCase {
    /**
     * @covers ::objectLocations
     */
    public function testObjectLocations(): void {
        // Prepare
        $owner    = Customer::factory()->make();
        $existing = LocationModel::factory(2)->make([
            'object_type' => $owner->getMorphClass(),
            'object_id'   => $owner,
        ]);

        $owner->setRelation('locations', $existing);

        $factory = new class(
            $this->app->make(LoggerInterface::class),
            $this->app->make(Normalizer::class),
            $this->app->make(LocationFactory::class),
            $this->app->make(TypeResolver::class),
        ) extends ModelFactory {
            use WithLocations {
                objectLocations as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                LoggerInterface $logger,
                Normalizer $normalizer,
                LocationFactory $locations,
                TypeResolver $types,
            ) {
                $this->logger     = $logger;
                $this->normalizer = $normalizer;
                $this->locations  = $locations;
                $this->types      = $types;
            }

            public function create(Type $type): ?Model {
                return null;
            }
        };

        // Empty call should return empty array
        $this->assertEquals([], $factory->objectLocations($owner, []));

        // Repeated objects should be missed
        $ca = tap(new Location(), function (Location $location): void {
            $location->zip          = $this->faker->postcode;
            $location->city         = $this->faker->city;
            $location->address      = $this->faker->streetAddress;
            $location->locationType = $this->faker->word;
        });

        $this->assertCount(1, $factory->objectLocations($owner, [$ca, $ca]));

        // Objects should be grouped by type
        $cb     = tap(new Location(), function (Location $location) use ($ca): void {
            $location->zip          = $ca->zip;
            $location->city         = $ca->city;
            $location->address      = $ca->address;
            $location->locationType = $this->faker->word;
        });
        $actual = $factory->objectLocations($owner, [$ca, $cb]);
        $first  = reset($actual);

        $this->assertCount(1, $actual);
        $this->assertCount(2, $first->types);
        $this->assertEquals($cb->zip, $first->postcode);
        $this->assertEquals($cb->city, $first->city->name);
        $this->assertEquals($cb->address, $first->line_one);
    }

    /**
     * @covers ::location
     */
    public function testLocation(): void {
        // Prepare
        $owner    = new Customer();
        $location = new Location();
        $factory  = Mockery::mock(LocationFactory::class);
        $factory
            ->shouldReceive('create')
            ->with($owner, $location)
            ->once()
            ->andReturns();

        $factory = new class($factory) extends ModelFactory {
            use WithLocations {
                location as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(LocationFactory $locations) {
                $this->locations = $locations;
            }

            public function create(Type $type): ?Model {
                return null;
            }
        };

        $factory->location($owner, $location);
    }
}
