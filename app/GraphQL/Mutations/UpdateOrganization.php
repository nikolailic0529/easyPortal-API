<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Organization;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Schema\CompanyBrandingData;
use App\Services\DataLoader\Schema\UpdateCompanyLogo;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Http\UploadedFile;

class UpdateOrganization {
    public function __construct(
        protected CurrentOrganization $organization,
        protected Client $client,
        protected Factory $storage,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        // Prepare
        $organization = $this->organization->get();
        $mutation     = new CompanyBrandingData([
            'id' => $organization->getKey(),
        ]);

        // Update properties
        $this->updateProperties($organization, $mutation, $args['input']);

        // Update Cosmos
        if (!$mutation->isEmpty()) {
            $this->client->updateBrandingData($mutation);
        }

        // Return
        return [
            'result' => $organization->save(),
        ];
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
                    $organization->{$property} = $value;
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
                case 'logo':
                    $organization->branding_logo_url = $this->client->updateCompanyLogo(new UpdateCompanyLogo([
                        'companyId' => $organization->getKey(),
                        'logo'      => $value,
                    ]));
                    break;
                case 'favicon':
                    if ($value instanceof UploadedFile) {
                        $organization->branding_favicon_url = $this->store($organization, $value);
                        $branding->favIconUrl               = $organization->branding_favicon_url;
                    } else {
                        $organization->branding_welcome_image_url = null;
                        $branding->mainImageOnTheRight            = null;
                    }
                    break;
                case 'welcome_heading':
                    $organization->branding_welcome_heading = $value;
                    $branding->mainHeadingText              = $value;
                    break;
                case 'welcome_underline':
                    $organization->branding_welcome_underline = $value;
                    $branding->underlineText                  = $value;
                    break;
                case 'welcome_image':
                    if ($value instanceof UploadedFile) {
                        $organization->branding_welcome_image_url = $this->store($organization, $value);
                        $branding->mainImageOnTheRight            = $organization->branding_welcome_image_url;
                    } else {
                        $organization->branding_welcome_image_url = null;
                        $branding->mainImageOnTheRight            = null;
                    }
                    break;
                default:
                    $organization->{$property} = $value;
                    break;
            }
        }
    }

    protected function store(Organization $organization, UploadedFile $file): string {
        $disk = 'public';
        $path = $file->storePublicly("{$organization->getMorphClass()}/{$organization->getKey()}", $disk);
        $url  = $this->storage->disk($disk)->url($path);

        return $url;
    }
}
