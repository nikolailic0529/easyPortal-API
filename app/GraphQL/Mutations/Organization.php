<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\CurrentTenant;
use Illuminate\Contracts\Filesystem\Filesystem;

use function array_key_exists;

class Organization {
    public function __construct(
        protected CurrentTenant $tenant,
        protected Filesystem $storage,
    ) {
        // empty
    }
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     */
    public function __invoke($_, array $args): bool {
        $currentTenant = $this->tenant->get();

        if (array_key_exists('locale', $args)) {
            $currentTenant->locale = $args['locale'];
        }

        if (array_key_exists('currency_id', $args)) {
            $currentTenant->currency_id = $args['currency_id'];
        }

        if (array_key_exists('branding_dark_theme', $args)) {
            $currentTenant->branding_dark_theme = $args['branding_dark_theme'];
        }

        if (array_key_exists('branding_primary_color', $args)) {
            $currentTenant->branding_primary_color = $args['branding_primary_color'];
        }

        if (array_key_exists('branding_secondary_color', $args)) {
            $currentTenant->branding_secondary_color = $args['branding_secondary_color'];
        }

        if (array_key_exists('branding_logo', $args)) {
            if ($this->storage->exists($currentTenant->branding_logo)) {
                $this->storage->delete($currentTenant->branding_logo);
            }

            $file                         = $args['branding_logo'];
            $currentTenant->branding_logo = $file->storePublicly('uploads');
        }

        if (array_key_exists('branding_favicon', $args)) {
            if ($this->storage->exists($currentTenant->branding_favicon)) {
                $this->storage->delete($currentTenant->branding_favicon);
            }

            $file                            = $args['branding_favicon'];
            $currentTenant->branding_favicon = $file->storePublicly('uploads');
        }

        $currentTenant->save();

        return true;
    }
}
