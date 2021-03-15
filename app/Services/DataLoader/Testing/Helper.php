<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing;

use App\Models\Customer;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\CompanyType;
use DateTimeInterface;
use libphonenumber\NumberParseException;
use Propaganistas\LaravelPhone\PhoneNumber;

use function array_map;
use function array_unique;
use function array_values;
use function is_null;
use function reset;

trait Helper {
    // <editor-fold desc="General">
    // =========================================================================
    protected function getDatetime(?DateTimeInterface $datetime): ?string {
        return $datetime
            ? "{$datetime->getTimestamp()}{$datetime->format('v')}"
            : null;
    }
    // </editor-fold>

    // <editor-fold desc="Company">
    // =========================================================================
    protected function getCompanyType(Company $company): string {
        $types = array_unique(array_map(static function (CompanyType $type): string {
            return $type->type;
        }, $company->companyTypes));

        return reset($types);
    }

    /**
     * @return array<mixed>
     */
    protected function getCompanyLocations(Company $company): array {
        $locations = [];

        foreach ($company->locations as $location) {
            // Add to array
            $key = "{$location->zip}/{$location->zip}/{$location->address}";

            if (isset($locations[$key])) {
                $locations[$key]['types'][] = $location->locationType;
            } else {
                $locations[$key] = [
                    'types'    => [$location->locationType],
                    'postcode' => $location->zip,
                    'state'    => '',
                    'city'     => $location->city,
                    'line_one' => $location->address,
                    'line_two' => '',
                ];
            }
        }

        return array_values($locations);
    }

    /**
     * @return array<mixed>
     */
    protected function getCompanyContacts(Company $company): array {
        $contacts = [];

        foreach ($company->companyContactPersons as $person) {
            // Empty?
            if (is_null($person->name) && is_null($person->phoneNumber)) {
                continue;
            }

            // Convert phone
            $phone = $person->phoneNumber;

            try {
                $phone = PhoneNumber::make($phone)->formatE164();
            } catch (NumberParseException) {
                // empty
            }

            // Add to array
            $key = "{$person->name}/{$phone}";

            if (isset($contacts[$key])) {
                $contacts[$key]['types'][] = $person->type;
            } else {
                $contacts[$key] = [
                    'name'  => $person->name,
                    'phone' => $phone,
                    'types' => [$person->type],
                ];
            }
        }

        return $contacts;
    }
    //</editor-fold>

    // <editor-fold desc="Customer">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    protected function getCustomerLocations(Customer $customer): array {
        $locations = [];

        foreach ($customer->locations as $location) {
            /** @var \App\Models\Location $location */
            $locations[] = $this->getLocation($location);
        }

        return $locations;
    }

    /**
     * @return array<mixed>
     */
    protected function getCustomerContacts(Customer $customer): array {
        $contacts = [];

        foreach ($customer->contacts as $contact) {
            $contacts["{$contact->name}/{$contact->phone_number}"] = [
                'name'  => $contact->name,
                'phone' => $contact->phone_number,
                'types' => $contact->types
                    ->map(static function (TypeModel $type): string {
                        return $type->name;
                    })
                    ->all(),
            ];
        }

        return $contacts;
    }

    /**
     * @return array<mixed>
     */
    protected function getLocation(Location $location, bool $withTypes = true): array {
        $types = [];

        if ($withTypes) {
            $types = $location->types
                ->map(static function (TypeModel $type): string {
                    return $type->name;
                })
                ->all();
        }

        return [
            'postcode' => $location->postcode,
            'state'    => $location->state,
            'city'     => $location->city->name,
            'line_one' => $location->line_one,
            'line_two' => $location->line_two,
            'types'    => $types,
        ];
    }
    //</editor-fold>

    // <editor-fold desc="Assets">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    protected function getAssetLocation(Asset $asset): array {
        return [
            'types'    => [],
            'postcode' => $asset->zip,
            'state'    => '',
            'city'     => $asset->city,
            'line_one' => $asset->address,
            'line_two' => '',
        ];
    }
    // </editor-fold>

    // <editor-fold desc="Organization">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    protected function getOrganizationLocations(Organization $organization): array {
        $locations = [];

        foreach ($organization->locations as $location) {
            /** @var \App\Models\Location $location */
            $locations[] = $this->getLocation($location);
        }

        return $locations;
    }
    // </editor-fold>
}
