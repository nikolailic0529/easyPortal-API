<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Organization\Organization;
use Illuminate\Contracts\Filesystem\Factory;

use function array_key_exists;
use function is_null;

class UpdateOrganization {
    public function __construct(
        protected Organization $organization,
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
        $organization = $this->organization->get();

        if (array_key_exists('locale', $args['input'])) {
            $organization->locale = $args['input']['locale'];
        }

        if (array_key_exists('currency_id', $args['input'])) {
            $organization->currency_id = $args['input']['currency_id'];
        }

        if (array_key_exists('branding_dark_theme', $args['input'])) {
            $organization->branding_dark_theme = $args['input']['branding_dark_theme'];
        }

        if (array_key_exists('branding_primary_color', $args['input'])) {
            $organization->branding_primary_color = $args['input']['branding_primary_color'];
        }

        if (array_key_exists('branding_secondary_color', $args['input'])) {
            $organization->branding_secondary_color = $args['input']['branding_secondary_color'];
        }

        if (array_key_exists('website_url', $args['input'])) {
            $organization->website_url = $args['input']['website_url'];
        }

        if (array_key_exists('email', $args['input'])) {
            $organization->email = $args['input']['email'];
        }

        if (array_key_exists('branding_logo', $args['input'])) {
            if ($this->storage->disk('local')->exists($organization->branding_logo)) {
                $this->storage->disk('local')->delete($organization->branding_logo);
            }

            $file = $args['input']['branding_logo'];
            if (is_null($file)) {
                $organization->branding_logo = $file;
            } else {
                $organization->branding_logo = $file->storePublicly('uploads');
            }
        }

        if (array_key_exists('branding_favicon', $args['input'])) {
            if ($this->storage->disk('local')->exists($organization->branding_favicon)) {
                $this->storage->disk('local')->delete($organization->branding_favicon);
            }

            $file = $args['input']['branding_favicon'];
            if (is_null($file)) {
                $organization->branding_favicon = $file;
            } else {
                $organization->branding_favicon = $file->storePublicly('uploads');
            }
        }

        $result = $organization->save();

        return ['result' => $result];
    }
}
