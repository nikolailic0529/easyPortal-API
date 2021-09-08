<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Exceptions\ErrorReport;
use App\Models\Customer;
use App\Models\Location as LocationModel;
use App\Models\Model;
use App\Services\DataLoader\Exceptions\FailedToProcessLocation;
use App\Services\DataLoader\Factories\LocationFactory;
use App\Services\DataLoader\Factories\ModelFactory;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Location;
use App\Services\DataLoader\Schema\Type;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use Mockery;
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
            $this->app->make(ExceptionHandler::class),
            $this->app->make(Normalizer::class),
            $this->app->make(TypeResolver::class),
            $this->app->make(LocationFactory::class),
            $this->app->make(Dispatcher::class),
        ) extends ModelFactory {
            use WithLocations {
                objectLocations as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ExceptionHandler $exceptionHandler,
                protected Normalizer $normalizer,
                protected TypeResolver $typeResolver,
                protected LocationFactory $locationFactory,
                protected Dispatcher $dispatcher,
            ) {
                // empty
            }

            public function create(Type $type): ?Model {
                return null;
            }

            protected function getLocationFactory(): LocationFactory {
                return $this->locationFactory;
            }

            protected function getDispatcher(): Dispatcher {
                return $this->dispatcher;
            }

            protected function getTypeResolver(): TypeResolver {
                return $this->typeResolver;
            }
        };

        // Empty call should return empty array
        $this->assertEquals([], $factory->objectLocations($owner, []));

        // Repeated objects should be missed
        $ca = tap(new Location(), function (Location $location): void {
            $location->country      = $this->faker->country;
            $location->countryCode  = $this->faker->countryCode;
            $location->latitude     = null;
            $location->longitude    = null;
            $location->zip          = $this->faker->postcode;
            $location->city         = $this->faker->city;
            $location->address      = $this->faker->streetAddress;
            $location->locationType = (string) $this->faker->randomNumber();
        });

        $this->assertCount(1, $factory->objectLocations($owner, [$ca, $ca]));

        // Objects should be grouped by type
        $cb     = tap(new Location(), function (Location $location) use ($ca): void {
            $location->country      = $ca->country;
            $location->countryCode  = $ca->countryCode;
            $location->latitude     = $ca->latitude;
            $location->longitude    = $ca->longitude;
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

        // locationType can be null
        $cc     = tap(clone $ca, static function (Location $location): void {
            $location->locationType = null;
        });
        $actual = $factory->objectLocations($owner, [$cc]);
        $first  = reset($actual);

        $this->assertCount(1, $actual);
        $this->assertCount(0, $first->types);
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

        $factory = new class($factory, $this->app->make(Dispatcher::class)) extends ModelFactory {
            use WithLocations {
                location as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected LocationFactory $locations,
                protected Dispatcher $dispatcher,
            ) {
                // empty
            }

            public function create(Type $type): ?Model {
                return null;
            }

            protected function getLocationFactory(): LocationFactory {
                return $this->locations;
            }

            protected function getDispatcher(): Dispatcher {
                return $this->dispatcher;
            }

            protected function getTypeResolver(): TypeResolver {
                throw new Exception('Should not be called.');
            }
        };

        $factory->location($owner, $location);
    }

    /**
     * @covers ::objectLocations
     */
    public function testObjectLocationsInvalidLocation(): void {
        // Fake
        Event::fake(ErrorReport::class);

        // Prepare
        $owner    = new Customer();
        $location = new Location();
        $factory  = Mockery::mock(LocationFactory::class);
        $factory
            ->shouldReceive('create')
            ->with($owner, $location)
            ->once()
            ->andThrow(new Exception(__METHOD__));

        $factory = new class(
            $factory,
            $this->app->make(Dispatcher::class),
            $this->app->make(ExceptionHandler::class),
        ) extends ModelFactory {
            use WithLocations {
                objectLocations as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected LocationFactory $locations,
                protected Dispatcher $dispatcher,
                protected ExceptionHandler $exceptionHandler,
            ) {
                // empty
            }

            public function create(Type $type): ?Model {
                return null;
            }

            protected function getLocationFactory(): LocationFactory {
                return $this->locations;
            }

            protected function getDispatcher(): Dispatcher {
                return $this->dispatcher;
            }

            protected function getTypeResolver(): TypeResolver {
                throw new Exception('Should not be called.');
            }
        };

        $this->assertEmpty($factory->objectLocations($owner, [$location]));

        Event::assertDispatched(ErrorReport::class, static function (ErrorReport $event): bool {
            return $event->getError() instanceof FailedToProcessLocation;
        });
    }
}
