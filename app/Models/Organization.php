<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasCurrency;
use App\Models\Concerns\HasLocations;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Organization.
 *
 * @property string                                                                 $id
 * @property string|null                                                            $subdomain
 * @property string                                                                 $name
 * @property string|null                                                            $locale
 * @property string|null                                                            $currency_id
 * @property bool                                                                   $branding_dark_theme
 * @property string|null                                                            $branding_primary_color
 * @property string|null                                                            $branding_secondary_color
 * @property string|null                                                            $branding_logo
 * @property string|null                                                            $branding_fav_icon
 * @property \Carbon\CarbonImmutable                                                $created_at
 * @property \Carbon\CarbonImmutable                                                $updated_at
 * @property \Carbon\CarbonImmutable|null                                           $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|array<\App\Models\Asset> $assets
 * @property \Illuminate\Database\Eloquent\Collection|array<\App\Models\Location>   $locations
 * @method static \Database\Factories\OrganizationFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereSubdomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Organization extends Model implements HasLocalePreference {
    use HasFactory;
    use HasCurrency;
    use HasLocations;

    protected const CASTS = [
        'branding_dark_theme' => 'bool',
    ];

    public const ROOT = '@root';

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

    public function isRoot(): bool {
        return $this->subdomain === self::ROOT;
    }

    public function preferredLocale(): ?string {
        return $this->locale;
    }
}
