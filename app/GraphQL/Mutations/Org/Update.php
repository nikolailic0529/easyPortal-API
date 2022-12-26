<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\Organization;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Schema\Inputs\CompanyBrandingData;
use App\Services\DataLoader\Schema\Inputs\InputTranslationText;
use App\Services\DataLoader\Schema\Inputs\UpdateCompanyFile;
use App\Services\Filesystem\ModelDiskFactory;
use App\Services\I18n\Eloquent\TranslatedString;
use App\Services\Keycloak\Utils\Map;
use Illuminate\Http\UploadedFile;

use function array_values;

class Update {
    public function __construct(
        protected Client $client,
        protected ModelDiskFactory $disks,
    ) {
        // empty
    }

    /**
     * @param array{input: array<string, mixed>} $args
     */
    public function __invoke(Organization $org, array $args): bool {
        // Prepare
        $input = new CompanyBrandingData([
            'id' => $org->getKey(),
        ]);

        // Update properties
        $this->updateProperties($org, $input, $args['input']);

        // Update Cosmos
        if ($input->count() > 1 && $org->company) {
            $this->client->updateBrandingData($input);
        }

        // Return
        return $org->save();
    }

    /**
     * @param array<mixed> $properties
     */
    protected function updateProperties(
        Organization $organization,
        CompanyBrandingData $branding,
        array $properties,
    ): void {
        foreach ($properties as $property => $value) {
            switch ($property) {
                case 'analytics_code':
                    $organization->analytics_code    = $value;
                    $branding->resellerAnalyticsCode = $value;
                    break;
                case 'branding':
                    $this->updateBranding($organization, $branding, $value);
                    break;
                default:
                    $organization->setAttribute($property, $value);
                    break;
            }
        }
    }

    /**
     * @param array<mixed> $properties
     */
    protected function updateBranding(
        Organization $organization,
        CompanyBrandingData $branding,
        array $properties,
    ): void {
        foreach ($properties as $property => $value) {
            switch ($property) {
                case 'dark_theme':
                    $organization->branding_dark_theme = $value;
                    $branding->brandingMode            = $value ? 'true' : 'false';
                    break;
                case 'main_color':
                    $organization->branding_main_color = $value;
                    $branding->mainColor               = $value;
                    break;
                case 'secondary_color':
                    $organization->branding_secondary_color = $value;
                    $branding->secondaryColor               = $value;
                    break;
                case 'logo_url':
                    if ($organization->company) {
                        $organization->branding_logo_url = $this->client->updateCompanyLogo(new UpdateCompanyFile([
                            'companyId' => $organization->getKey(),
                            'file'      => $value,
                        ]));
                    } else {
                        $organization->branding_logo_url = $this->store($organization, $value);
                    }
                    break;
                case 'favicon_url':
                    if ($organization->company) {
                        $organization->branding_favicon_url = $this->client->updateCompanyFavicon(
                            new UpdateCompanyFile([
                                'companyId' => $organization->getKey(),
                                'file'      => $value,
                            ]),
                        );
                    } else {
                        $organization->branding_favicon_url = $this->store($organization, $value);
                    }
                    break;
                case 'welcome_heading':
                    $organization->branding_welcome_heading = $this->getTranslatedString($value);
                    $branding->mainHeadingText              = $this->getTranslationText($value);
                    break;
                case 'welcome_underline':
                    $organization->branding_welcome_underline = $this->getTranslatedString($value);
                    $branding->underlineText                  = $this->getTranslationText($value);
                    break;
                case 'welcome_image_url':
                    if ($organization->company) {
                        $organization->branding_welcome_image_url = $this->client->updateCompanyMainImageOnTheRight(
                            new UpdateCompanyFile([
                                'companyId' => $organization->getKey(),
                                'file'      => $value,
                            ]),
                        );
                    } else {
                        $organization->branding_welcome_image_url = $this->store($organization, $value);
                    }
                    break;
                case 'dashboard_image_url':
                    $organization->branding_dashboard_image_url = $this->store($organization, $value);
                    break;
                default:
                    $organization->setAttribute($property, $value);
                    break;
            }
        }
    }

    protected function store(Organization $organization, ?UploadedFile $file): ?string {
        $url = null;

        if ($file) {
            $disk = $this->disks->getDisk($organization);
            $url  = $disk->url($disk->store($file));
        }

        return $url;
    }

    /**
     * @param array<array{locale:string,text:string}>|null $translations
     */
    protected function getTranslatedString(?array $translations): ?TranslatedString {
        $string = null;

        if ($translations) {
            $string = new TranslatedString();

            foreach ($translations as $translation) {
                $string[$translation['locale']] = $translation['text'];
            }
        }

        return $string;
    }

    /**
     * @param array<array{locale:string,text:string}>|null $translations
     *
     * @return array<InputTranslationText>|null
     */
    protected function getTranslationText(?array $translations): ?array {
        $texts = null;

        if ($translations) {
            $texts = [];

            foreach ($translations as $translation) {
                $appLocale      = $translation['locale'];
                $keycloakLocale = Map::getKeycloakLocale($appLocale);

                $texts[$appLocale]      = new InputTranslationText([
                    'language_code' => $appLocale,
                    'text'          => $translation['text'],
                ]);
                $texts[$keycloakLocale] = new InputTranslationText([
                    'language_code' => $keycloakLocale,
                    'text'          => $translation['text'],
                ]);
            }

            $texts = array_values($texts);
        }

        return $texts;
    }
}
