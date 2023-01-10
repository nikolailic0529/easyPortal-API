<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Models\Reseller;
use App\Models\ResellerLocation;
use App\Services\DataLoader\Events\ResellerUpdated;
use App\Services\DataLoader\Factory\CompanyFactory;
use App\Services\DataLoader\Factory\Concerns\WithKpi;
use App\Services\DataLoader\Resolver\Resolvers\CityResolver;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolver\Resolvers\CountryResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolver\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\Types\Company;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;

use function implode;
use function sprintf;

/**
 * @extends CompanyFactory<Reseller, ResellerLocation>
 */
class ResellerFactory extends CompanyFactory {
    use WithKpi;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        TypeResolver $typeResolver,
        StatusResolver $statusResolver,
        ContactResolver $contactReseller,
        LocationResolver $locationResolver,
        CountryResolver $countryResolver,
        CityResolver $cityResolver,
        protected Dispatcher $dispatcher,
        protected ResellerResolver $resellerResolver,
    ) {
        parent::__construct(
            $exceptionHandler,
            $typeResolver,
            $statusResolver,
            $contactReseller,
            $locationResolver,
            $countryResolver,
            $cityResolver,
        );
    }

    // <editor-fold desc="Factory">
    // =========================================================================
    public function getModel(): string {
        return Reseller::class;
    }

    public function create(Type $type, bool $force = false): ?Reseller {
        $model = null;

        if ($type instanceof Company) {
            $model = $this->createFromCompany($type, $force);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                implode('`, `', [
                    Company::class,
                ]),
            ));
        }

        return $model;
    }
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function createFromCompany(Company $company, bool $force): ?Reseller {
        // Get/Create
        $created  = false;
        $factory  = function (Reseller $reseller) use ($force, &$created, $company): Reseller {
            // Unchanged?
            $created = !$reseller->exists;
            $hash    = $company->getHash();

            if ($force === false && $hash === $reseller->hash) {
                return $reseller;
            }

            // Update
            $reseller->id         = $company->id;
            $reseller->hash       = $hash;
            $reseller->name       = $company->name;
            $reseller->changed_at = $company->updatedAt;
            $reseller->statuses   = $this->companyStatuses($reseller, $company);
            $reseller->contacts   = $this->contacts($reseller, $company->companyContactPersons);
            $reseller->locations  = $this->companyLocations($reseller, $company->locations);
            $reseller->kpi        = $this->kpi($reseller, $company->companyKpis);

            if ($created) {
                $reseller->assets_count    = 0;
                $reseller->customers_count = 0;
            }

            if ($reseller->trashed()) {
                $reseller->restore();
            } else {
                $reseller->save();
            }

            $this->dispatcher->dispatch(new ResellerUpdated($reseller, $company));

            return $reseller;
        };
        $reseller = $this->resellerResolver->get(
            $company->id,
            static function () use ($factory): Reseller {
                return $factory(new Reseller());
            },
        );

        // Update
        if (!$created) {
            $factory($reseller);
        }

        // Return
        return $reseller;
    }
    // </editor-fold>
}
