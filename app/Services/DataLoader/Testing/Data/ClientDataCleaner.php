<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use App\Services\DataLoader\Schema\CentralAssetDbStatistics;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\CompanyContactPerson;
use App\Services\DataLoader\Schema\CompanyKpis;
use App\Services\DataLoader\Schema\CompanyType;
use App\Services\DataLoader\Schema\CoverageEntry;
use App\Services\DataLoader\Schema\CoverageStatusCheck;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\DocumentVendorSpecificField;
use App\Services\DataLoader\Schema\Location;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewCompany;
use App\Services\DataLoader\Schema\ViewDocument;
use Exception;
use Faker\Generator;

use function array_key_exists;
use function sprintf;

/**
 * Replaces real DataLoader data to fake data.
 */
class ClientDataCleaner {
    /**
     * @var array<string,string>
     */
    protected array $map = [];

    public function __construct(
        protected Generator $faker,
    ) {
        // empty
    }

    public function clean(object $object): void {
        $uuid           = function (): string {
            return $this->faker->uuid;
        };
        $text           = function (): string {
            return $this->faker->sentence;
        };
        $company        = function (): string {
            return $this->faker->company;
        };
        $latitude       = function (): string {
            return (string) $this->faker->latitude;
        };
        $longitude      = function (): string {
            return (string) $this->faker->longitude;
        };
        $city           = function (): string {
            return $this->faker->city;
        };
        $countryName    = function (): string {
            return $this->faker->country;
        };
        $countryCode    = function (): string {
            return $this->faker->countryCode;
        };
        $postcode       = function (): string {
            return $this->faker->postcode;
        };
        $addressLineOne = function (): string {
            return $this->faker->streetAddress;
        };
        $addressLineTwo = function (): string {
            return $this->faker->secondaryAddress;
        };
        $phone          = function (): string {
            return $this->faker->e164PhoneNumber;
        };
        $email          = function (): string {
            return $this->faker->email;
        };
        $name           = function (): string {
            return $this->faker->name;
        };

        if ($object instanceof ViewAsset) {
            $object->serialNumber = $this->map($object->serialNumber, $uuid);
            $object->zip          = $this->map($object->zip, $postcode);
            $object->city         = $this->map($object->city, $city);
            $object->address      = $this->map($object->address, $addressLineOne);
            $object->address2     = $this->map($object->address2, $addressLineTwo);
            $object->country      = $this->map($object->country, $countryName);
            $object->countryCode  = $this->map($object->countryCode, $countryCode);
            $object->latitude     = $this->map($object->latitude, $latitude);
            $object->longitude    = $this->map($object->longitude, $longitude);
        } elseif ($object instanceof ViewAssetDocument) {
            $object->documentNumber = $this->map($object->documentNumber, $uuid);
        } elseif ($object instanceof ViewDocument) {
            $object->documentNumber = $this->map($object->documentNumber, $uuid);
        } elseif ($object instanceof Document) {
            $object->documentNumber = $this->map($object->documentNumber, $uuid);
        } elseif ($object instanceof DocumentVendorSpecificField) {
            $object->said             = $this->map($object->said, $uuid);
            $object->groupId          = $this->map($object->groupId, $uuid);
            $object->groupDescription = $this->map($object->groupDescription, $text);
        } elseif ($object instanceof ViewCompany) {
            if (isset($object->name)) {
                $object->name = $this->map($object->name, $company);
            }
        } elseif ($object instanceof Company) {
            $object->name                    = $this->map($object->name, $company);
            $object->keycloakName            = null;
            $object->keycloakGroupId         = null;
            $object->keycloakClientScopeName = null;
        } elseif ($object instanceof CompanyContactPerson) {
            $object->phoneNumber = $this->map($object->phoneNumber, $phone);
            $object->name        = $this->map($object->name, $name);
            $object->mail        = $this->map($object->mail, $email);
        } elseif ($object instanceof CompanyType) {
            // empty
        } elseif ($object instanceof Location) {
            $object->zip         = $this->map($object->zip, $postcode);
            $object->city        = $this->map($object->city, $city);
            $object->address     = $this->map($object->address, $addressLineOne);
            $object->country     = $this->map($object->country, $countryName);
            $object->countryCode = $this->map($object->countryCode, $countryCode);
            $object->latitude    = $this->map($object->latitude, $latitude);
            $object->longitude   = $this->map($object->longitude, $longitude);
        } elseif ($object instanceof CompanyKpis) {
            // empty
        } elseif ($object instanceof CentralAssetDbStatistics) {
            // empty
        } elseif ($object instanceof CoverageStatusCheck) {
            // empty
        } elseif ($object instanceof CoverageEntry) {
            // empty
        } else {
            throw new Exception(sprintf(
                'Object of class `%s` is not supported.',
                $object::class,
            ));
        }
    }

    protected function map(string|null $value, callable $mapper): string|null {
        if ($value === null || $value === '') {
            return $value;
        }

        if (!array_key_exists($value, $this->map)) {
            $this->map[$value] = $mapper($this->faker, $value);
        }

        return $this->map[$value];
    }
}
