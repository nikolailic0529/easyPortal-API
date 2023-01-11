<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory;

use App\Exceptions\ErrorReport;
use App\Models\Customer;
use App\Models\Reseller;
use App\Services\DataLoader\Exceptions\FailedToProcessLocation;
use App\Services\DataLoader\Resolver\Resolvers\CityResolver;
use App\Services\DataLoader\Resolver\Resolvers\CountryResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolver\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\Types\Company as CompanyObject;
use App\Services\DataLoader\Schema\Types\Location;
use App\Services\DataLoader\Testing\Helper;
use App\Utils\Eloquent\Model;
use Closure;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

use function tap;

/**
 * @internal
 * @covers \App\Services\DataLoader\Factory\CompanyFactory
 */
class CompanyFactoryTest extends TestCase {
    use Helper;

    // <editor-fold desc="Tests">
    // =========================================================================
    public function testCompanyStatuses(): void {
        // Prepare
        $owner   = new class() extends Model {
            public function getMorphClass(): string {
                return $this::class;
            }
        };
        $factory = new class(
            $this->app->make(StatusResolver::class),
        ) extends CompanyFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected StatusResolver $statusResolver,
            ) {
                // empty
            }

            public function getModel(): string {
                return Model::class;
            }

            public function create(Type $type, bool $force = false): ?Model {
                return null;
            }

            public function companyStatuses(Model $owner, CompanyObject $company): Collection {
                return parent::companyStatuses($owner, $company);
            }
        };

        // Null
        self::assertEmpty($factory->companyStatuses($owner, new CompanyObject(['status' => null])));

        // Empty
        self::assertEmpty($factory->companyStatuses($owner, new CompanyObject(['status' => ['', null]])));

        // Not empty
        $company  = new CompanyObject([
            'status' => ['a', 'A', 'b'],
        ]);
        $statuses = $factory->companyStatuses($owner, $company);
        $expected = [
            'a' => [
                'key'  => 'a',
                'name' => 'A',
            ],
            'b' => [
                'key'  => 'b',
                'name' => 'B',
            ],
        ];

        self::assertCount(2, $statuses);
        self::assertEquals($expected, $this->statuses($statuses));
    }

    /**
     * @dataProvider dataProviderCompanyLocations
     *
     * @param Closure(static): (Reseller|Customer) $companyFactory
     */
    public function testCompanyLocations(Closure $companyFactory): void {
        // Prepare
        $company = $companyFactory($this);
        $factory = new class(
            $this->app->make(LocationResolver::class),
            $this->app->make(CountryResolver::class),
            $this->app->make(CityResolver::class),
            $this->app->make(TypeResolver::class),
        ) extends CompanyFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected LocationResolver $locationResolver,
                protected CountryResolver $countryResolver,
                protected CityResolver $cityResolver,
                protected TypeResolver $typeResolver,
            ) {
                // empty
            }

            public function getModel(): string {
                return Model::class;
            }

            public function create(Type $type, bool $force = false): ?Model {
                return null;
            }

            /**
             * @inheritDoc
             */
            public function companyLocations(Reseller|Customer $company, array $locations): Collection {
                return parent::companyLocations($company, $locations);
            }
        };

        // Empty call should return empty array
        self::assertTrue($factory->companyLocations($company, [])->isEmpty());

        // Repeated objects should be missed
        $ca = tap(new Location(), function (Location $location): void {
            $location->country      = $this->faker->country();
            $location->countryCode  = $this->faker->countryCode();
            $location->latitude     = null;
            $location->longitude    = null;
            $location->zip          = $this->faker->postcode();
            $location->city         = $this->faker->city();
            $location->address      = $this->faker->streetAddress();
            $location->locationType = (string) $this->faker->randomNumber();
        });

        self::assertCount(1, $factory->companyLocations($company, [$ca, $ca]));

        // Objects should be grouped by type
        $cb     = tap(new Location(), function (Location $location) use ($ca): void {
            $location->country      = $ca->country;
            $location->countryCode  = $ca->countryCode;
            $location->latitude     = $ca->latitude;
            $location->longitude    = $ca->longitude;
            $location->zip          = $ca->zip;
            $location->city         = $ca->city;
            $location->address      = $ca->address;
            $location->locationType = $this->faker->word();
        });
        $actual = $factory->companyLocations($company, [$ca, $cb]);
        $first  = $actual->first();

        self::assertNotNull($first);
        self::assertCount(1, $actual);
        self::assertCount(2, $first->types);
        self::assertEquals($cb->zip, $first->location->postcode);
        self::assertEquals($cb->city, $first->location->city->name);
        self::assertEquals($cb->address, $first->location->line_one);

        // locationType can be null
        $cc     = tap(clone $ca, static function (Location $location): void {
            $location->locationType = null;
        });
        $actual = $factory->companyLocations($company, [$cc]);
        $first  = $actual->first();

        self::assertNotNull($first);
        self::assertCount(1, $actual);
        self::assertCount(0, $first->types);
    }

    public function testCompanyLocationsInvalidLocation(): void {
        // Fake
        Event::fake(ErrorReport::class);

        // Prepare
        $owner    = new Customer();
        $location = new Location();
        $handler  = $this->app->make(ExceptionHandler::class);
        $factory  = Mockery::mock(CompanyFactory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('getExceptionHandler')
            ->once()
            ->andReturn($handler);
        $factory
            ->shouldReceive('location')
            ->once()
            ->andThrow(new Exception(__METHOD__));

        self::assertEmpty($factory->companyLocations($owner, [$location]));

        Event::assertDispatched(ErrorReport::class, static function (ErrorReport $event): bool {
            return $event->getError() instanceof FailedToProcessLocation;
        });
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{Closure(): (Reseller|Customer)}>
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
