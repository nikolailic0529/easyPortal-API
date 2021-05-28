<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing;

use App\Models\Customer;
use App\Models\Location;
use App\Models\Model;
use App\Models\Reseller;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\CompanyType;
use App\Services\DataLoader\Schema\Document;
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

    /**
     * @return array<mixed>
     */
    protected function getModelContacts(Model $model): array {
        $contacts = [];

        foreach ($model->contacts as $contact) {
            $contacts["{$contact->name}/{$contact->phone_number}"] = [
                'name'  => $contact->name,
                'phone' => $contact->phone_number,
                'mail'  => $contact->email,
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
    protected function getModelTags(Model $model): array {
        $tags = [];

        foreach ($model->tags as $tag) {
            $tags["{$tag->name}"] = [
                'name' => $tag->name,
            ];
        }

        return $tags;
    }

    /**
     * @return array<mixed>
     */
    protected function getTags(Asset $object): array {
        $output = [];
        $tags   = [];

        if ($object instanceof Asset) {
            $tags = [$object->assetTag];
        } else {
            // empty
        }

        foreach ($tags as $tag) {
            // Add to array
            $output[$tag] = [
                'name'  => $tag,
            ];
        }

        return $output;
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
            // Is empty
            if (!$location->zip || !$location->city) {
                continue;
            }

            // Add to array
            $key = "{$location->zip}/{$location->zip}/{$location->address}";

            if (isset($locations[$key])) {
                $locations[$key]['types'][] = $location->locationType;
            } else {
                $locations[$key] = [
                    'types'       => [$location->locationType],
                    'country'     => $location->country ?? 'Unknown Country',
                    'countryCode' => $location->countryCode ?? '??',
                    'postcode'    => $location->zip,
                    'state'       => '',
                    'city'        => $location->city,
                    'line_one'    => $location->address,
                    'line_two'    => '',
                    'latitude'    => $location->latitude,
                    'longitude'   => $location->longitude,
                ];
            }
        }

        return array_values($locations);
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
    protected function getLocation(?Location $location, bool $withTypes = true): ?array {
        if (!$location) {
            return null;
        }

        $types = [];

        if ($withTypes) {
            $types = $location->types
                ->map(static function (TypeModel $type): string {
                    return $type->name;
                })
                ->all();
        }

        return [
            'country'     => $location->country->name,
            'countryCode' => $location->country->code,
            'postcode'    => $location->postcode,
            'state'       => $location->state,
            'city'        => $location->city->name,
            'line_one'    => $location->line_one,
            'line_two'    => $location->line_two,
            'types'       => $types,
            'latitude'    => $location->latitude,
            'longitude'   => $location->longitude,
        ];
    }
    //</editor-fold>

    // <editor-fold desc="Assets">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    protected function getAssetLocation(Asset $asset): ?array {
        return $asset->zip && $asset->city ? [
            'types'       => [],
            'country'     => $asset->country,
            'countryCode' => $asset->countryCode,
            'postcode'    => $asset->zip,
            'state'       => '',
            'city'        => $asset->city,
            'line_one'    => $asset->address,
            'line_two'    => '',
            'latitude'    => $asset->latitude,
            'longitude'   => $asset->longitude,
        ] : null;
    }

    /**
     * @return array<mixed>
     */
    protected function getContacts(Asset|Company|Document $object): array {
        $contacts = [];
        $persons  = [];

        if ($object instanceof Document) {
            $persons = (array) $object->contactPersons;
        } elseif ($object instanceof Company) {
            $persons = $object->companyContactPersons;
        } elseif ($object instanceof Asset) {
            $persons = $object->latestContactPersons;
        } else {
            // empty
        }

        foreach ($persons as $person) {
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
                    'mail'  => $person->mail,
                ];
            }
        }

        return $contacts;
    }
    // </editor-fold>

    // <editor-fold desc="Reseller">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    protected function getResellerLocations(Reseller $reseller): array {
        $locations = [];

        foreach ($reseller->locations as $location) {
            /** @var \App\Models\Location $location */
            $locations[] = $this->getLocation($location);
        }

        return $locations;
    }
    // </editor-fold>
}
