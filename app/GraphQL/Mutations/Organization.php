<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Tenant\Tenant;
use Illuminate\Contracts\Filesystem\Factory;

use function array_key_exists;
use function is_null;

class Organization {
    public function __construct(
        protected Tenant $tenant,
        protected Factory $storage,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     */
    public function __invoke($_, array $args): bool {
        $organization = $this->tenant->get();

        if (array_key_exists('locale', $args)) {
            $organization->locale = $args['locale'];
        }

        if (array_key_exists('currency_id', $args)) {
            $organization->currency_id = $args['currency_id'];
        }

        if (array_key_exists('branding_dark_theme', $args)) {
            $organization->branding_dark_theme = $args['branding_dark_theme'];
        }

        if (array_key_exists('branding_primary_color', $args)) {
            $organization->branding_primary_color = $args['branding_primary_color'];
        }

        if (array_key_exists('branding_secondary_color', $args)) {
            $organization->branding_secondary_color = $args['branding_secondary_color'];
        }

        if (array_key_exists('website_url', $args)) {
            $organization->website_url = $args['website_url'];
        }

        if (array_key_exists('email', $args)) {
            $organization->email = $args['email'];
        }

        if (array_key_exists('branding_logo', $args)) {
            if ($this->storage->disk('local')->exists($organization->branding_logo)) {
                $this->storage->disk('local')->delete($organization->branding_logo);
            }

            $file = $args['branding_logo'];
            if (is_null($file)) {
                $organization->branding_logo = $file;
            } else {
                $organization->branding_logo = $file->storePublicly('uploads');
            }
        }

        if (array_key_exists('branding_favicon', $args)) {
            if ($this->storage->disk('local')->exists($organization->branding_favicon)) {
                $this->storage->disk('local')->delete($organization->branding_favicon);
            }

            $file = $args['branding_favicon'];
            if (is_null($file)) {
                $organization->branding_favicon = $file;
            } else {
                $organization->branding_favicon = $file->storePublicly('uploads');
            }
        }

        $organization->save();

        return true;
    }
}
