<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Data\Coverage;
use App\Models\Data\Location;
use App\Models\Data\Status;
use App\Models\Data\Type as TypeModel;
use App\Models\Document as DocumentModel;
use App\Models\Reseller;
use App\Models\ResellerLocation;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Finders\AssetFinder as AssetFinderContract;
use App\Services\DataLoader\Finders\CustomerFinder as CustomerFinderContract;
use App\Services\DataLoader\Finders\DistributorFinder as DistributorFinderContract;
use App\Services\DataLoader\Finders\ResellerFinder as ResellerFinderContract;
use App\Services\DataLoader\Normalizers\NameNormalizer;
use App\Services\DataLoader\Schema\Types\Company;
use App\Services\DataLoader\Schema\Types\Document;
use App\Services\DataLoader\Schema\Types\ViewAsset;
use App\Services\DataLoader\Schema\Types\ViewDocument;
use App\Services\DataLoader\Testing\Data\Data;
use App\Services\DataLoader\Testing\Data\DataGenerator;
use App\Services\DataLoader\Testing\Finders\AssetFinder;
use App\Services\DataLoader\Testing\Finders\CustomerFinder;
use App\Services\DataLoader\Testing\Finders\DistributorFinder;
use App\Services\DataLoader\Testing\Finders\ResellerFinder;
use DateTimeInterface;
use Illuminate\Support\Collection;
use libphonenumber\NumberParseException;
use Propaganistas\LaravelPhone\PhoneNumber;
use Tests\TestCase;

use function array_values;
use function is_null;

/**
 * @mixin TestCase
 */
trait Helper {
    // <editor-fold desc="General">
    // =========================================================================
    protected function latitude(string|null $latitude): ?string {
        $model           = new Location();
        $model->latitude = $latitude;

        return $model->latitude;
    }

    protected function longitude(string|null $longitude): ?string {
        $model            = new Location();
        $model->longitude = $longitude;

        return $model->longitude;
    }

    protected function getDatetime(?DateTimeInterface $datetime): ?string {
        return $datetime
            ? "{$datetime->getTimestamp()}{$datetime->format('v')}"
            : null;
    }

    /**
     * @return array<mixed>
     */
    protected function getModelContacts(Reseller|Customer|Asset|DocumentModel $model): array {
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
    protected function getModelTags(Asset $model): array {
        $tags = [];

        foreach ($model->tags ?? [] as $tag) {
            /** @var \App\Models\Data\Tag $tag */
            $tags["{$tag->name}"] = [
                'name' => $tag->name,
            ];
        }

        return $tags;
    }

    /**
     * @return array<mixed>
     */
    protected function getModelCoverages(Asset $model): array {
        $coverages = [];

        foreach ($model->coverages ?? [] as $coverage) {
            /** @var Coverage $coverage */

            $coverages["{$coverage->key}"] = [
                'key'  => $coverage->key,
                'name' => $coverage->name,
            ];
        }

        return $coverages;
    }

    /**
     * @param Collection<int, Coverage>|array<Coverage> $coverages
     *
     * @return array<string, array{key: string, name: string}>|null
     */
    protected function getCoverages(Collection|array|null $coverages): ?array {
        $result = null;

        if ($coverages) {
            foreach ($coverages as $coverage) {
                /** @var \App\Models\Data\Coverage $coverage */

                $result["{$coverage->key}"] = [
                    'key'  => $coverage->key,
                    'name' => $coverage->name,
                ];
            }
        }

        return $result;
    }

    /**
     * @return array<string, array{key: string, name: string}>|null
     */
    protected function getModelStatuses(DocumentModel|Reseller|Customer $model): ?array {
        return $this->statuses($model->statuses ?? []);
    }

    /**
     * @param Collection<int, Status>|array<Status> $statuses
     *
     * @return array<string, array{key: string, name: string}>|null
     */
    protected function statuses(Collection|array|null $statuses): ?array {
        $result = null;

        if ($statuses) {
            foreach ($statuses as $status) {
                /** @var Status $status */

                $result["{$status->key}"] = [
                    'key'  => $status->key,
                    'name' => $status->name,
                ];
            }
        }

        return $result;
    }

    /**
     * @return array<string, array{key: string, name: string}>|null
     */
    protected function getStatuses(Company|Document $object): ?array {
        $statuses = [];

        foreach ((array) $object->status as $status) {
            $statuses[$status] = [
                'key'  => $status,
                'name' => NameNormalizer::normalize($status),
            ];
        }

        return $statuses;
    }

    /**
     * @return array<string, array{name: ?string, phone: ?string, mail: ?string, types: array<string>}>
     */
    protected function getContacts(ViewAsset|Company|ViewDocument $object): array {
        $contacts = [];
        $persons  = [];

        if ($object instanceof ViewDocument) {
            $persons = (array) $object->contactPersons;
        } elseif ($object instanceof Company) {
            $persons = $object->companyContactPersons;
        } else {
            $persons = (array) $object->latestContactPersons;
        }

        foreach ($persons as $person) {
            // Empty?
            if (is_null($person->name) && is_null($person->phoneNumber)) {
                continue;
            }

            // Convert phone
            $phone = $person->phoneNumber;

            try {
                $phone = $phone
                    ? PhoneNumber::make($phone)->formatE164()
                    : null;
            } catch (NumberParseException) {
                // empty
            }

            // Add to array
            $key = "{$person->name}/{$phone}";

            if (isset($contacts[$key])) {
                $contacts[$key]['types'][] = NameNormalizer::normalize($person->type);
            } else {
                $contacts[$key] = [
                    'name'  => $person->name,
                    'phone' => $phone,
                    'types' => [NameNormalizer::normalize($person->type)],
                    'mail'  => $person->mail,
                ];
            }
        }

        return $contacts;
    }
    // </editor-fold>

    // <editor-fold desc="Company">
    // =========================================================================
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
                $locations[$key]['types'][] = NameNormalizer::normalize($location->locationType);
            } else {
                $locations[$key] = [
                    'types'       => [NameNormalizer::normalize($location->locationType)],
                    'country'     => $location->country ?? 'Unknown Country',
                    'countryCode' => $location->countryCode ?? '??',
                    'postcode'    => $location->zip,
                    'state'       => '',
                    'city'        => $location->city,
                    'line_one'    => $location->address,
                    'line_two'    => '',
                    'latitude'    => $this->latitude($location->latitude),
                    'longitude'   => $this->longitude($location->longitude),
                ];
            }
        }

        return array_values($locations);
    }
    //</editor-fold>

    // <editor-fold desc="Customer / Reseller">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    protected function getCompanyModelLocations(Customer|Reseller $company): array {
        $locations = [];

        foreach ($company->locations as $companyLocation) {
            /** @var ResellerLocation|CustomerLocation $companyLocation */
            $location          = $this->getLocation($companyLocation->location);
            $location['types'] = $companyLocation->types
                ->map(static function (TypeModel $type): string {
                    return $type->name;
                })
                ->all();

            $locations[] = $location;
        }

        return $locations;
    }

    /**
     * @return array<mixed>
     */
    protected function getLocation(?Location $location): ?array {
        if (!$location) {
            return null;
        }

        return [
            'country'     => $location->country->name,
            'countryCode' => $location->country->code,
            'postcode'    => $location->postcode,
            'state'       => $location->state,
            'city'        => $location->city->name,
            'line_one'    => $location->line_one,
            'line_two'    => $location->line_two,
            'types'       => [],
            'latitude'    => $this->latitude($location->latitude),
            'longitude'   => $this->longitude($location->longitude),
        ];
    }
    //</editor-fold>

    // <editor-fold desc="Assets">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    protected function getAssetLocation(ViewAsset $asset): ?array {
        return $asset->zip && $asset->city ? [
            'types'       => [],
            'country'     => $asset->country,
            'countryCode' => $asset->countryCode,
            'postcode'    => $asset->zip,
            'state'       => '',
            'city'        => $asset->city,
            'line_one'    => $asset->address,
            'line_two'    => '',
            'latitude'    => $this->latitude($asset->latitude),
            'longitude'   => $this->longitude($asset->longitude),
        ] : null;
    }

    /**
     * @return array<string, array{name: ?string}>
     */
    protected function getAssetTags(ViewAsset $object): array {
        $tags = [];

        foreach ((array) $object->assetTag as $tag) {
            // Add to array
            $tags[$tag] = [
                'name' => $tag,
            ];
        }

        return $tags;
    }

    /**
     * @return array<string, array{key: string, name: string}>
     */
    protected function getAssetCoverages(ViewAsset $object): array {
        $coverages = [];

        foreach ((array) $object->assetCoverage as $coverage) {
            // Add to array
            $coverages[$coverage] = [
                'key'  => $coverage,
                'name' => NameNormalizer::normalize($coverage),
            ];
        }

        return $coverages;
    }
    // </editor-fold>

    // <editor-fold desc="Finders">
    // =========================================================================
    protected function overrideFinders(): void {
        $this->overrideDistributorFinder();
        $this->overrideResellerFinder();
        $this->overrideCustomerFinder();
    }

    protected function overrideDistributorFinder(): void {
        $this->override(DistributorFinderContract::class, static function (): DistributorFinderContract {
            return new DistributorFinder();
        });
    }

    protected function overrideResellerFinder(): void {
        $this->override(ResellerFinderContract::class, static function (): ResellerFinderContract {
            return new ResellerFinder();
        });
    }

    protected function overrideCustomerFinder(): void {
        $this->override(CustomerFinderContract::class, static function (): CustomerFinderContract {
            return new CustomerFinder();
        });
    }

    protected function overrideAssetFinder(): void {
        $this->override(AssetFinderContract::class, static function (): AssetFinderContract {
            return new AssetFinder();
        });
    }
    // </editor-fold>

    // <editor-fold desc="Data">
    // =========================================================================
    /**
     * @param class-string<Data> $data
     */
    protected function generateData(string $data): void {
        // Generate
        self::assertTrue($this->app->make(DataGenerator::class)->generate($data));

        // Setup
        $this->overrideDateFactory('2021-08-30T00:00:00.000+00:00');
        $this->overrideUuidFactory('fbce8f60-847f-4ecb-9ba7-898bbda41dd2');

        $this->override(Client::class, function () use ($data): Client {
            return $this->app->make(FakeClient::class)
                ->setLimit($data::LIMIT)
                ->setData($this->getTestData($data));
        });

        // Restore
        self::assertTrue($this->app->make(DataGenerator::class)->restore($data));

        // Reset
        $this->resetDateFactory();
        $this->resetUuidFactory();
    }
    //</editor-fold>
}
