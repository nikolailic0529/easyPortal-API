<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\CurrentTenant;
use Illuminate\Contracts\Filesystem\Factory;

use function array_key_exists;

class UpdateOrganization {
    public function __construct(
        protected CurrentTenant $tenant,
        protected Factory $storage,
    ) {
        // empty
    }
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        $currentTenant = $this->tenant->get();

        if (array_key_exists('locale', $args['input'])) {
            $currentTenant->locale = $args['input']['locale'];
        }

        if (array_key_exists('currency_id', $args['input'])) {
            $currentTenant->currency_id = $args['input']['currency_id'];
        }

        if (array_key_exists('branding_dark_theme', $args['input'])) {
            $currentTenant->branding_dark_theme = $args['input']['branding_dark_theme'];
        }

        if (array_key_exists('branding_primary_color', $args['input'])) {
            $currentTenant->branding_primary_color = $args['input']['branding_primary_color'];
        }

        if (array_key_exists('branding_secondary_color', $args['input'])) {
            $currentTenant->branding_secondary_color = $args['input']['branding_secondary_color'];
        }

        if (array_key_exists('website_url', $args['input'])) {
            $currentTenant->website_url = $args['input']['website_url'];
        }

        if (array_key_exists('email', $args['input'])) {
            $currentTenant->email = $args['input']['email'];
        }

        if (array_key_exists('branding_logo', $args['input'])) {
            if ($this->storage->disk('local')->exists($currentTenant->branding_logo)) {
                $this->storage->disk('local')->delete($currentTenant->branding_logo);
            }

            $file                         = $args['input']['branding_logo'];
            $currentTenant->branding_logo = $file->storePublicly('uploads');
        }

        if (array_key_exists('branding_favicon', $args['input'])) {
            if ($this->storage->disk('local')->exists($currentTenant->branding_favicon)) {
                $this->storage->disk('local')->delete($currentTenant->branding_favicon);
            }

            $file                            = $args['input']['branding_favicon'];
            $currentTenant->branding_favicon = $file->storePublicly('uploads');
        }

        $result = $currentTenant->save();

        return [ 'result' => $result ];
    }
}
