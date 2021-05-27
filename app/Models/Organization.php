<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasCurrency;
use App\Models\Concerns\HasLocations;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Organization.
 *
 * @property string                                                         $id
 * @property string                                                         $name
 * @property string|null                                                    $keycloak_scope
 * @property string|null                                                    $keycloak_group_id
 * @property string|null                                                    $locale
 * @property string|null                                                    $currency_id
 * @property string|null                                                    $website_url
 * @property string|null                                                    $email
 * @property bool|null                                                      $branding_dark_theme
 * @property string|null                                                    $branding_main_color
 * @property string|null                                                    $branding_secondary_color
 * @property string|null                                                    $branding_logo_url
 * @property string|null                                                    $branding_favicon_url
 * @property string|null                                                    $branding_default_main_color
 * @property string|null                                                    $branding_default_secondary_color
 * @property string|null                                                    $branding_default_logo_url
 * @property string|null                                                    $branding_default_favicon_url
 * @property string|null                                                    $branding_welcome_image_url
 * @property string|null                                                    $branding_welcome_heading
 * @property string|null                                                    $branding_welcome_underline
 * @property string|null                                                    $analytics_code
 * @property \Carbon\CarbonImmutable                                        $created_at
 * @property \Carbon\CarbonImmutable                                        $updated_at
 * @property \Carbon\CarbonImmutable|null                                   $deleted_at
 * @property \App\Models\Currency|null                                      $currency
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Location> $locations
 * @method static \Database\Factories\OrganizationFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization query()
 * @mixin \Eloquent
 */
class Organization extends Model implements HasLocalePreference {
    use HasFactory;
    use HasCurrency;
    use HasLocations;

    protected const CASTS = [
        'branding_dark_theme' => 'bool',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS + parent::CASTS;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'organizations';

    public function preferredLocale(): ?string {
        return $this->locale;
    }
}
