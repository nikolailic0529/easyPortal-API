<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory;

use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Data\Status;
use App\Models\Reseller;
use App\Models\ResellerLocation;
use App\Services\DataLoader\Exceptions\FailedToProcessLocation;
use App\Services\DataLoader\Factory\Concerns\Polymorphic;
use App\Services\DataLoader\Factory\Concerns\WithContacts;
use App\Services\DataLoader\Factory\Concerns\WithLocations;
use App\Services\DataLoader\Factory\Concerns\WithStatus;
use App\Services\DataLoader\Factory\Concerns\WithType;
use App\Services\DataLoader\Resolver\Resolvers\CityResolver;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolver\Resolvers\CountryResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolver\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Types\Company;
use App\Services\DataLoader\Schema\Types\Location;
use App\Utils\Eloquent\Model;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

use function array_values;

/**
 * @template TCompany of Reseller|Customer
 * @template TLocation of ResellerLocation|CustomerLocation
 *
 * @extends ModelFactory<TCompany>
 */
abstract class CompanyFactory extends ModelFactory {
    use Polymorphic;
    use WithType;
    use WithStatus;
    use WithContacts;
    use WithLocations;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        protected TypeResolver $typeResolver,
        protected StatusResolver $statusResolver,
        protected ContactResolver $contactReseller,
        protected LocationResolver $locationResolver,
        protected CountryResolver $countryResolver,
        protected CityResolver $cityResolver,
    ) {
        parent::__construct($exceptionHandler);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getContactsResolver(): ContactResolver {
        return $this->contactReseller;
    }

    protected function getLocationResolver(): LocationResolver {
        return $this->locationResolver;
    }

    protected function getCountryResolver(): CountryResolver {
        return $this->countryResolver;
    }

    protected function getCityResolver(): CityResolver {
        return $this->cityResolver;
    }

    protected function getStatusResolver(): StatusResolver {
        return $this->statusResolver;
    }

    protected function getTypeResolver(): TypeResolver {
        return $this->typeResolver;
    }
    // </editor-fold>

    // <editor-fold desc="Company">
    // =========================================================================
    /**
     * @return Collection<int, Status>
     */
    protected function companyStatuses(Model $owner, Company $company): Collection {
        $statuses = [];

        foreach ($company->status ?? [] as $status) {
            if ($status) {
                $status                      = $this->status($owner, $status);
                $statuses[$status->getKey()] = $status;
            }
        }

        return Collection::make(array_values($statuses));
    }

    /**
     * @param TCompany        $company
     * @param array<Location> $locations
     *
     * @return Collection<array-key, TLocation>
     */
    protected function companyLocations(Reseller|Customer $company, array $locations): Collection {
        $companyLocations = $company->locations
            ->keyBy(static function (ResellerLocation|CustomerLocation $location): string {
                return $location->location_id;
            });

        return $this->polymorphic(
            $company,
            $locations,
            static function (Location $location): ?string {
                return $location->locationType;
            },
            function (
                Reseller|Customer $company,
                Location $location,
            ) use (
                $companyLocations,
            ): ResellerLocation|CustomerLocation|null {
                try {
                    $locationModel   = $this->location($location);
                    $companyLocation = null;

                    if ($locationModel) {
                        $companyLocation = $companyLocations->get($locationModel->getKey());

                        if (!$companyLocation) {
                            if ($company instanceof Reseller) {
                                $companyLocation           = new ResellerLocation();
                                $companyLocation->reseller = $company;
                            } else {
                                $companyLocation           = new CustomerLocation();
                                $companyLocation->customer = $company;
                            }

                            $companyLocation->location = $locationModel;

                            $companyLocations->put($locationModel->getKey(), $companyLocation);
                        }
                    }

                    return $companyLocation;
                } catch (Throwable $exception) {
                    $this->getExceptionHandler()->report(
                        new FailedToProcessLocation($company, $location, $exception),
                    );
                }

                return null;
            },
        );
    }
    // </editor-fold>
}
