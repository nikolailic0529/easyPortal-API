<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Tenant\Tenant;
use Illuminate\Contracts\Filesystem\Factory;

use function array_key_exists;
use function is_null;

class UpdateOrganization {
    public function __construct(
        protected Tenant $tenant,
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
        $tenant = $this->tenant->get();

        if (array_key_exists('locale', $args['input'])) {
            $tenant->locale = $args['input']['locale'];
        }

        if (array_key_exists('currency_id', $args['input'])) {
            $tenant->currency_id = $args['input']['currency_id'];
        }

        if (array_key_exists('branding_dark_theme', $args['input'])) {
            $tenant->branding_dark_theme = $args['input']['branding_dark_theme'];
        }

        if (array_key_exists('branding_primary_color', $args['input'])) {
            $tenant->branding_primary_color = $args['input']['branding_primary_color'];
        }

        if (array_key_exists('branding_secondary_color', $args['input'])) {
            $tenant->branding_secondary_color = $args['input']['branding_secondary_color'];
        }

        if (array_key_exists('website_url', $args['input'])) {
            $tenant->website_url = $args['input']['website_url'];
        }

        if (array_key_exists('email', $args['input'])) {
            $tenant->email = $args['input']['email'];
        }

        if (array_key_exists('branding_logo', $args['input'])) {
            if ($this->storage->disk('local')->exists($tenant->branding_logo)) {
                $this->storage->disk('local')->delete($tenant->branding_logo);
            }

            $file = $args['input']['branding_logo'];
            if (is_null($file)) {
                $tenant->branding_logo = $file;
            } else {
                $tenant->branding_logo = $file->storePublicly('uploads');
            }
        }

        if (array_key_exists('branding_favicon', $args['input'])) {
            if ($this->storage->disk('local')->exists($tenant->branding_favicon)) {
                $this->storage->disk('local')->delete($tenant->branding_favicon);
            }

            $file = $args['input']['branding_favicon'];
            if (is_null($file)) {
                $tenant->branding_favicon = $file;
            } else {
                $tenant->branding_favicon = $file->storePublicly('uploads');
            }
        }

        $result = $tenant->save();

        return ['result' => $result];
    }
}
