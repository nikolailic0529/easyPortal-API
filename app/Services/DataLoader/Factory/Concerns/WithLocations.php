<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Location as LocationModel;
use App\Models\Reseller;
use App\Models\ResellerLocation;
use App\Services\DataLoader\Exceptions\FailedToProcessLocation;
use App\Services\DataLoader\Factory\Factories\LocationFactory;
use App\Services\DataLoader\Schema\Location;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Throwable;

/**
 * @template C of \App\Models\Reseller|\App\Models\Customer
 * @template L of \App\Models\ResellerLocation|\App\Models\CustomerLocation
 */
trait WithLocations {
    use Polymorphic;

    abstract protected function getExceptionHandler(): ExceptionHandler;

    abstract protected function getLocationFactory(): LocationFactory;

    /**
     * @param C               $company
     * @param array<Location> $locations
     *
     * @return array<L>
     */
    protected function companyLocations(Reseller|Customer $company, array $locations): array {
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

    protected function location(Location $location): ?LocationModel {
        return $this->getLocationFactory()->create($location);
    }
}
