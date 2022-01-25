<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document as DocumentModel;
use App\Models\Location;
use App\Models\Reseller;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Finders\AssetFinder as AssetFinderContract;
use App\Services\DataLoader\Finders\CustomerFinder as CustomerFinderContract;
use App\Services\DataLoader\Finders\DistributorFinder as DistributorFinderContract;
use App\Services\DataLoader\Finders\OemFinder as OemFinderContract;
use App\Services\DataLoader\Finders\ResellerFinder as ResellerFinderContract;
use App\Services\DataLoader\Finders\ServiceGroupFinder as ServiceGroupFinderContract;
use App\Services\DataLoader\Finders\ServiceLevelFinder as ServiceLevelFinderContract;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\CompanyType;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewDocument;
use App\Services\DataLoader\Testing\Data\DataGenerator;
use App\Services\DataLoader\Testing\Finders\AssetFinder;
use App\Services\DataLoader\Testing\Finders\CustomerFinder;
use App\Services\DataLoader\Testing\Finders\DistributorFinder;
use App\Services\DataLoader\Testing\Finders\OemFinder;
use App\Services\DataLoader\Testing\Finders\ResellerFinder;
use App\Services\DataLoader\Testing\Finders\ServiceGroupFinder;
use App\Services\DataLoader\Testing\Finders\ServiceLevelFinder;
use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use libphonenumber\NumberParseException;
use Propaganistas\LaravelPhone\PhoneNumber;
use Tests\Helpers\SequenceDateFactory;
use Tests\Helpers\SequenceUuidFactory;

use function array_map;
use function array_unique;
use function array_values;
use function in_array;
use function is_null;
use function reset;
use function str_ends_with;

/**
 * @mixin \Tests\TestCase
 */
trait Helper {
    // <editor-fold desc="General">
    // =========================================================================
    protected function latitude(string|float|null $latitude): ?string {
        $model           = new Location();
        $model->latitude = $latitude;

        return $model->latitude;
    }

    protected function longitude(string|float|null $longitude): ?string {
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
            /** @var \App\Models\Tag $tag */
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
            /** @var \App\Models\Coverage $coverage */

            $coverages["{$coverage->key}"] = [
                'key'  => $coverage->key,
                'name' => $coverage->name,
            ];
        }

        return $coverages;
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Coverage>|array<\App\Models\Coverage> $coverages
     *
     * @return array{key: string, name: string}|null
     */
    protected function getCoverages(Collection|array|null $coverages): ?array {
        $result = null;

        foreach ($coverages as $coverage) {
            /** @var \App\Models\Coverage $coverage */

            $result["{$coverage->key}"] = [
                'key'  => $coverage->key,
                'name' => $coverage->name,
            ];
        }

        return $result;
    }

    /**
     * @return array{key: string, name: string}|null
     */
    protected function getModelStatuses(DocumentModel|Reseller|Customer $model): ?array {
        return $this->statuses($model->statuses ?? []);
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Status>|array<\App\Models\Status> $statuses
     *
     * @return array{key: string, name: string}|null
     */
    protected function statuses(Collection|array|null $statuses): ?array {
        $result = null;

        foreach ($statuses as $status) {
            /** @var \App\Models\Status $status */

            $result["{$status->key}"] = [
                'key'  => $status->key,
                'name' => $status->name,
            ];
        }

        return $result;
    }

    /**
     * @return array{key: string, name: string}|null
     */
    protected function getStatuses(Company|Document $object): ?array {
        $statuses = [];

        foreach ((array) $object->status as $status) {
            $statuses[$status] = [
                'key'  => $status,
                'name' => $status,
            ];
        }

        return $statuses;
    }

    /**
     * @return array<mixed>
     */
    protected function getContacts(ViewAsset|Company|ViewDocument $object): array {
        $contacts = [];
        $persons  = [];

        if ($object instanceof ViewDocument) {
            $persons = (array) $object->contactPersons;
        } elseif ($object instanceof Company) {
            $persons = $object->companyContactPersons;
        } elseif ($object instanceof ViewAsset) {
            $persons = (array) $object->latestContactPersons;
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

    /**
     * @param \Illuminate\Support\Collection<\Illuminate\Database\Eloquent\Model>
     *     |\Illuminate\Database\Eloquent\Model $model
     * @param array<string> $attributes
     *
     * @return array<string, mixed>
     */
    protected function getModelCountableProperties(Collection|Model $model, array $attributes = []): array {
        $properties = [];

        if ($model instanceof Collection) {
            $properties = $model
                ->map(function (Model $model) use ($attributes): array {
                    return $this->getModelCountableProperties($model, $attributes);
                })
                ->all();
        } else {
            foreach ($model->getAttributes() as $attribute => $value) {
                if (str_ends_with($attribute, '_count') || in_array($attribute, $attributes, true)) {
                    $properties[$attribute] = $value;
                }
            }
        }

        return $properties;
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
            /** @var \App\Models\ResellerLocation|\App\Models\CustomerLocation $companyLocation */
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
     * @return array<mixed>
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
     * @return array<mixed>
     */
    protected function getAssetCoverages(ViewAsset $object): array {
        $coverages = [];

        foreach ((array) $object->assetCoverage as $coverage) {
            // Add to array
            $coverages[$coverage] = [
                'key'  => $coverage,
                'name' => $coverage,
            ];
        }

        return $coverages;
    }
    // </editor-fold>

    // <editor-fold desc="Finders">
    // =========================================================================
    protected function overrideFinders(): void {
        $this->overrideServiceGroupFinder();
        $this->overrideServiceLevelFinder();
        $this->overrideDistributorFinder();
        $this->overrideResellerFinder();
        $this->overrideCustomerFinder();
        $this->overrideOemFinder();
    }

    protected function overrideOemFinder(): void {
        $this->override(OemFinderContract::class, static function (): OemFinderContract {
            return new OemFinder();
        });
    }

    protected function overrideServiceGroupFinder(): void {
        $this->override(ServiceGroupFinderContract::class, static function (): ServiceGroupFinderContract {
            return new ServiceGroupFinder();
        });
    }

    protected function overrideServiceLevelFinder(): void {
        $this->override(ServiceLevelFinderContract::class, static function (): ServiceLevelFinderContract {
            return new ServiceLevelFinder();
        });
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
     * @param class-string<\App\Services\DataLoader\Testing\Data\Data> $data
     */
    protected function generateData(string $data): void {
        // Generate
        $this->assertTrue($this->app->make(DataGenerator::class)->generate($data));

        // Setup
        $this->overrideDateFactory();
        $this->overrideUuidFactory();

        $this->override(Client::class, function () use ($data): Client {
            return $this->app->make(FakeClient::class)->setData($data);
        });

        // Restore
        $this->assertTrue($this->app->make(DataGenerator::class)->restore($data));
    }
    //</editor-fold>

    // <editor-fold desc="Others">
    // =========================================================================
    protected function overrideUuidFactory(): void {
        Str::createUuidsUsing(new SequenceUuidFactory());
    }

    protected function overrideDateFactory(): void {
        Date::setTestNow(Closure::fromCallable(new SequenceDateFactory()));
    }
    // </editor-fold>
}
