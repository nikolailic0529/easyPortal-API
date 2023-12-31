<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Enums\OrganizationType;
use App\Models\Organization;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method Organization create($attributes = [], ?Model $parent = null)
 * @method Organization make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Organization::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'                               => $this->faker->uuid(),
            'name'                             => $this->faker->company(),
            'type'                             => OrganizationType::reseller(),
            'keycloak_name'                    => null,
            'keycloak_scope'                   => null,
            'keycloak_group_id'                => null,
            'locale'                           => null,
            'timezone'                         => null,
            'currency_id'                      => null,
            'website_url'                      => null,
            'email'                            => null,
            'analytics_code'                   => null,
            'branding_dark_theme'              => $this->faker->boolean(),
            'branding_main_color'              => null,
            'branding_secondary_color'         => null,
            'branding_logo_url'                => null,
            'branding_favicon_url'             => null,
            'branding_default_main_color'      => $this->faker->hexColor(),
            'branding_default_secondary_color' => $this->faker->hexColor(),
            'branding_default_logo_url'        => null,
            'branding_default_favicon_url'     => null,
            'branding_welcome_image_url'       => null,
            'branding_welcome_heading'         => null,
            'branding_welcome_underline'       => null,
            'created_at'                       => Date::now(),
            'updated_at'                       => Date::now(),
            'deleted_at'                       => null,
            'branding_dashboard_image_url'     => null,
        ];
    }
}
