<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Exceptions\ErrorReport;
use App\Models\Customer;
use App\Models\Reseller;
use App\Services\DataLoader\Exceptions\FailedToProcessLocation;
use App\Services\DataLoader\Factory\Factories\LocationFactory;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Location;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use Closure;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;
use Tests\WithoutOrganizationScope;

use function reset;
use function tap;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithLocations
 */
class WithLocationsTest extends TestCase {
    use WithoutOrganizationScope;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::companyLocations
     *
     * @dataProvider dataProviderCompanyLocations
     *
     * @param \Closure(static): \App\Models\Reseller|\App\Models\Customer
     */
    public function testCompanyLocations(Closure $companyFactory): void {
        // Prepare
        $company = $companyFactory($this);
        $factory = new class(
            $this->app->make(ExceptionHandler::class),
            $this->app->make(Normalizer::class),
            $this->app->make(TypeResolver::class),
            $this->app->make(LocationFactory::class),
        ) extends ModelFactory {
            use WithLocations {
                companyLocations as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ExceptionHandler $exceptionHandler,
                protected Normalizer $normalizer,
                protected TypeResolver $typeResolver,
                protected LocationFactory $locationFactory,
            ) {
                // empty
            }

            public function create(Type $type): ?Model {
                return null;
            }

            protected function getLocationFactory(): LocationFactory {
                return $this->locationFactory;
            }

            protected function getTypeResolver(): TypeResolver {
                return $this->typeResolver;
            }
        };

        // Empty call should return empty array
        $this->assertEquals([], $factory->companyLocations($company, []));

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

        $this->assertCount(1, $factory->companyLocations($company, [$ca, $ca]));

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
        $actual = $factory->companyLocations($company, [$ca, $cb]);
        $first  = reset($actual);

        $this->assertCount(1, $actual);
        $this->assertCount(2, $first->types);
        $this->assertEquals($cb->zip, $first->location->postcode);
        $this->assertEquals($cb->city, $first->location->city->name);
        $this->assertEquals($cb->address, $first->location->line_one);

        // locationType can be null
        $cc     = tap(clone $ca, static function (Location $location): void {
            $location->locationType = null;
        });
        $actual = $factory->companyLocations($company, [$cc]);
        $first  = reset($actual);

        $this->assertCount(1, $actual);
        $this->assertCount(0, $first->types);
    }

    /**
     * @covers ::location
     */
    public function testLocation(): void {
        $location = new Location();
        $factory  = Mockery::mock(LocationFactory::class);
        $factory
            ->shouldReceive('create')
            ->with($location)
            ->once()
            ->andReturns();

        $factory = new class($factory) extends ModelFactory {
            use WithLocations {
                location as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected LocationFactory $locations,
            ) {
                // empty
            }

            public function create(Type $type): ?Model {
                return null;
            }

            protected function getLocationFactory(): LocationFactory {
                return $this->locations;
            }

            protected function getTypeResolver(): TypeResolver {
                throw new Exception('Should not be called.');
            }
        };

        $factory->location($location);
    }

    /**
     * @covers ::companyLocations
     */
    public function testCompanyLocationsInvalidLocation(): void {
        // Fake
        Event::fake(ErrorReport::class);

        // Prepare
        $owner    = new Customer();
        $location = new Location();
        $factory  = Mockery::mock(LocationFactory::class);
        $factory
            ->shouldReceive('create')
            ->with($location)
            ->once()
            ->andThrow(new Exception(__METHOD__));

        $factory = new class(
            $factory,
            $this->app->make(ExceptionHandler::class),
        ) extends ModelFactory {
            use WithLocations {
                companyLocations as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected LocationFactory $locations,
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

            protected function getTypeResolver(): TypeResolver {
                throw new Exception('Should not be called.');
            }
        };

        $this->assertEmpty($factory->companyLocations($owner, [$location]));

        Event::assertDispatched(ErrorReport::class, static function (ErrorReport $event): bool {
            return $event->getError() instanceof FailedToProcessLocation;
        });
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array<\Closure(): \App\Models\Reseller|\App\Models\Customer>>
     */
    public function dataProviderCompanyLocations(): array {
        return [
            Reseller::class => [
                static function (): Reseller {
                    return Reseller::factory()->create();
                },
            ],
            Customer::class => [
                static function (): Customer {
                    return Customer::factory()->create();
                },
            ],
        ];
    }
    // </editor-fold>
}
