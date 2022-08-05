<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use App\Services\DataLoader\Schema\BrandingData;
use App\Services\DataLoader\Schema\CentralAssetDbStatistics;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\CompanyContactPerson;
use App\Services\DataLoader\Schema\CompanyKpis;
use App\Services\DataLoader\Schema\CompanyType;
use App\Services\DataLoader\Schema\CoverageEntry;
use App\Services\DataLoader\Schema\CoverageStatusCheck;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\DocumentEntry;
use App\Services\DataLoader\Schema\DocumentVendorSpecificField;
use App\Services\DataLoader\Schema\Location;
use App\Services\DataLoader\Schema\TranslationText;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewCompany;
use App\Services\DataLoader\Schema\ViewDocument;
use App\Services\DataLoader\Testing\Data\Fake\AddressLineOne as AddressLineOneValue;
use App\Services\DataLoader\Testing\Data\Fake\AddressLineTwo as AddressLineTwoValue;
use App\Services\DataLoader\Testing\Data\Fake\City as CityValue;
use App\Services\DataLoader\Testing\Data\Fake\Company as CompanyValue;
use App\Services\DataLoader\Testing\Data\Fake\CountryCode as CountryCodeValue;
use App\Services\DataLoader\Testing\Data\Fake\CountryName as CountryNameValue;
use App\Services\DataLoader\Testing\Data\Fake\Email as EmailValue;
use App\Services\DataLoader\Testing\Data\Fake\ImageUrl;
use App\Services\DataLoader\Testing\Data\Fake\Latitude as LatitudeValue;
use App\Services\DataLoader\Testing\Data\Fake\Longitude as LongitudeValue;
use App\Services\DataLoader\Testing\Data\Fake\Name as NameValue;
use App\Services\DataLoader\Testing\Data\Fake\Phone as PhoneValue;
use App\Services\DataLoader\Testing\Data\Fake\Postcode as PostcodeValue;
use App\Services\DataLoader\Testing\Data\Fake\Text as TextValue;
use App\Services\DataLoader\Testing\Data\Fake\Uuid as UuidValue;
use App\Services\DataLoader\Testing\Data\Fake\Value;
use Exception;

use function array_key_exists;
use function array_slice;
use function sha1;
use function sprintf;

/**
 * Replaces real DataLoader data to fake data.
 */
class ClientDataCleaner {
    /**
     * @var array<string,string>
     */
    protected array $map = [];
    /**
     * @var array<string,string>
     */
    protected array $default = [];

    public function __construct(
        protected UuidValue $uuid,
        protected TextValue $text,
        protected CompanyValue $company,
        protected LatitudeValue $latitude,
        protected LongitudeValue $longitude,
        protected CityValue $city,
        protected CountryCodeValue $countryCode,
        protected CountryNameValue $countryName,
        protected PostcodeValue $postcode,
        protected AddressLineOneValue $addressLineOne,
        protected AddressLineTwoValue $addressLineTwo,
        protected PhoneValue $phone,
        protected EmailValue $email,
        protected NameValue $name,
        protected ImageUrl $imageUrl,
    ) {
        // empty
    }

    /**
     * @return array<string,string>
     */
    public function getMap(): array {
        return $this->map;
    }

    /**
     * @param array<string,string> $map
     */
    public function setDefaultMap(array $map): static {
        $this->default = $map;

        return $this;
    }

    public function clean(object $object): void {
        if ($object instanceof ViewAsset) {
            $object->serialNumber  = $this->map($object->serialNumber, $this->uuid);
            $object->zip           = $this->map($object->zip, $this->postcode);
            $object->city          = $this->map($object->city, $this->city);
            $object->address       = $this->map($object->address, $this->addressLineOne);
            $object->address2      = $this->map($object->address2, $this->addressLineTwo);
            $object->country       = $this->map($object->country, $this->countryName);
            $object->countryCode   = $this->map($object->countryCode, $this->countryCode);
            $object->latitude      = $this->map($object->latitude, $this->latitude);
            $object->longitude     = $this->map($object->longitude, $this->longitude);
            $object->assetDocument = $this->limit($object->assetDocument ?? []);
        } elseif ($object instanceof ViewAssetDocument) {
            $object->documentNumber = $this->map($object->documentNumber, $this->uuid);
        } elseif ($object instanceof ViewDocument) {
            $object->documentNumber = $this->map($object->documentNumber, $this->uuid);
        } elseif ($object instanceof Document) {
            $object->documentNumber  = $this->map($object->documentNumber, $this->uuid);
            $object->documentEntries = $this->limit($object->documentEntries);
        } elseif ($object instanceof DocumentVendorSpecificField) {
            $object->said             = $this->map($object->said, $this->uuid);
            $object->groupId          = $this->map($object->groupId, $this->uuid);
            $object->groupDescription = $this->map($object->groupDescription, $this->text);
        } elseif ($object instanceof ViewCompany) {
            if (isset($object->name)) {
                $object->name = $this->map($object->name, $this->company);
            }
        } elseif ($object instanceof Company) {
            $object->name                    = $this->map($object->name, $this->company);
            $object->keycloakName            = null;
            $object->keycloakGroupId         = null;
            $object->keycloakClientScopeName = null;
        } elseif ($object instanceof CompanyContactPerson) {
            $object->phoneNumber = $this->map($object->phoneNumber, $this->phone);
            $object->name        = $this->map($object->name, $this->name);
            $object->mail        = $this->map($object->mail, $this->email);
        } elseif ($object instanceof CompanyType) {
            // empty
        } elseif ($object instanceof Location) {
            $object->zip         = $this->map($object->zip, $this->postcode);
            $object->city        = $this->map($object->city, $this->city);
            $object->address     = $this->map($object->address, $this->addressLineOne);
            $object->country     = $this->map($object->country, $this->countryName);
            $object->countryCode = $this->map($object->countryCode, $this->countryCode);
            $object->latitude    = $this->map($object->latitude, $this->latitude);
            $object->longitude   = $this->map($object->longitude, $this->longitude);
        } elseif ($object instanceof BrandingData) {
            $object->defaultLogoUrl      = $this->map($object->defaultLogoUrl, $this->imageUrl);
            $object->favIconUrl          = $this->map($object->favIconUrl, $this->imageUrl);
            $object->useDefaultFavIcon   = $this->map($object->useDefaultFavIcon, $this->imageUrl);
            $object->mainImageOnTheRight = $this->map($object->mainImageOnTheRight, $this->imageUrl);
            $object->logoUrl             = $this->map($object->logoUrl, $this->imageUrl);
        } elseif ($object instanceof TranslationText) {
            $object->text = $this->map($object->text, $this->text);
        } elseif ($object instanceof DocumentEntry) {
            // empty
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

    /**
     * @template V
     *
     * @param V $value
     *
     * @return V
     */
    protected function map(mixed $value, Value $mapper): mixed {
        if ($value === null || $value === '') {
            return $value;
        }

        $class = $mapper::class;
        $key   = sha1("{$class}@{$value}");

        if (!array_key_exists($key, $this->map)) {
            $this->map[$key] = $this->default[$key] ?? $mapper();
        }

        return $this->map[$key];
    }

    /**
     * @template T
     *
     * @param array<T>|null $items
     *
     * @return ($items is null ? null : array<T>)
     */
    protected function limit(?array $items): ?array {
        return $items === null ? $items : array_slice($items, 0, 5);
    }
}
