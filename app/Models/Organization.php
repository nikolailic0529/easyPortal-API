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
 * @property bool|null                                                      $branding_dark_theme
 * @property string|null                                                    $branding_primary_color
 * @property string|null                                                    $branding_secondary_color
 * @property string|null                                                    $branding_logo
 * @property string|null                                                    $branding_favicon
 * @property string|null                                                    $website_url
 * @property string|null                                                    $email
 * @property \Carbon\CarbonImmutable                                        $created_at
 * @property \Carbon\CarbonImmutable                                        $updated_at
 * @property \Carbon\CarbonImmutable|null                                   $deleted_at
 * @property \App\Models\Currency|null                                      $currency
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Location> $locations
 * @property-read int|null                                                  $locations_count
 * @method static \Database\Factories\OrganizationFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereBrandingDarkTheme($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereBrandingFavicon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereBrandingLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereBrandingPrimaryColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereBrandingSecondaryColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereKeycloakScope($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereSubdomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereWebsiteUrl($value)
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
