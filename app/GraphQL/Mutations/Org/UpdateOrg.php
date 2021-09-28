<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\Organization;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Schema\CompanyBrandingData;
use App\Services\DataLoader\Schema\UpdateCompanyFile;
use App\Services\Filesystem\ModelDiskFactory;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Http\UploadedFile;

class UpdateOrg {
    public function __construct(
        protected CurrentOrganization $organization,
        protected Client $client,
        protected ModelDiskFactory $disks,
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
        if ($mutation->count() > 1 && $organization->reseller) {
            $this->client->updateBrandingData($mutation);
        }

        // Return
        return [
            'result'       => $organization->save(),
            'organization' => $organization,
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
                case 'logo_url':
                    if ($organization->reseller) {
                        $organization->branding_logo_url = $this->client->updateCompanyLogo(new UpdateCompanyFile([
                            'companyId' => $organization->getKey(),
                            'file'      => $value,
                        ]));
                    } else {
                        $organization->branding_logo_url = $this->store($organization, $value);
                    }
                    break;
                case 'favicon_url':
                    if ($organization->reseller) {
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
                    $organization->branding_welcome_heading = $value;
                    $branding->mainHeadingText              = $value;
                    break;
                case 'welcome_underline':
                    $organization->branding_welcome_underline = $value;
                    $branding->underlineText                  = $value;
                    break;
                case 'welcome_image_url':
                    if ($organization->reseller) {
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
                    $organization->{$property} = $value;
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
}
