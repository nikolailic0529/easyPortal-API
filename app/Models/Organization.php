<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Audits\Audit;
use App\Models\Enums\OrganizationType;
use App\Models\Relations\HasChangeRequests;
use App\Models\Relations\HasCurrency;
use App\Services\Audit\Concerns\Auditable;
use App\Services\I18n\Contracts\HasTimezonePreference;
use App\Services\I18n\Eloquent\TranslatedString;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\OrganizationFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Organization.
 *
 * @property string                            $id
 * @property string                            $name
 * @property OrganizationType                  $type
 * @property string|null                       $keycloak_name
 * @property string|null                       $keycloak_scope
 * @property string|null                       $keycloak_group_id
 * @property string|null                       $locale
 * @property string|null                       $currency_id
 * @property string|null                       $website_url
 * @property string|null                       $email
 * @property string|null                       $timezone
 * @property string|null                       $analytics_code
 * @property bool|null                         $branding_dark_theme
 * @property string|null                       $branding_main_color
 * @property string|null                       $branding_secondary_color
 * @property string|null                       $branding_logo_url
 * @property string|null                       $branding_favicon_url
 * @property string|null                       $branding_default_main_color
 * @property string|null                       $branding_default_secondary_color
 * @property string|null                       $branding_default_logo_url
 * @property string|null                       $branding_default_favicon_url
 * @property string|null                       $branding_welcome_image_url
 * @property TranslatedString|null             $branding_welcome_heading
 * @property TranslatedString|null             $branding_welcome_underline
 * @property string|null                       $branding_dashboard_image_url
 * @property CarbonImmutable                   $created_at
 * @property CarbonImmutable                   $updated_at
 * @property CarbonImmutable|null              $deleted_at
 * @property-read Collection<int, Audit>       $audits
 * @property-read Reseller|null                $company
 * @property Currency|null                     $currency
 * @property Collection<int, OrganizationUser> $organizationUsers
 * @property-read Collection<int, Role>        $roles
 * @property-read Collection<int, User>        $users
 * @method static OrganizationFactory factory(...$parameters)
 * @method static Builder|Organization newModelQuery()
 * @method static Builder|Organization newQuery()
 * @method static Builder|Organization query()
 */
class Organization extends Model implements
    HasLocalePreference,
    HasTimezonePreference,
    Auditable {
    use HasFactory;
    use HasCurrency;
    use HasChangeRequests;

    protected const CASTS = [
        'type'                       => OrganizationType::class,
        'branding_dark_theme'        => 'bool',
        'branding_welcome_heading'   => TranslatedString::class,
        'branding_welcome_underline' => TranslatedString::class,
    ] + parent::CASTS;

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'organizations';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $visible = [
        'branding_dark_theme',
        'branding_main_color',
        'branding_secondary_color',
        'branding_logo_url',
        'branding_favicon_url',
        'branding_default_main_color',
        'branding_default_secondary_color',
        'branding_default_logo_url',
        'branding_default_favicon_url',
        'branding_welcome_image_url',
        'branding_welcome_heading',
        'branding_welcome_underline',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function preferredLocale(): ?string {
        return $this->locale;
    }

    public function preferredTimezone(): ?string {
        return $this->timezone;
    }

    /**
     * @return HasOne<Reseller>
     */
    #[CascadeDelete(false)]
    public function company(): HasOne {
        return $this->hasOne(Reseller::class, (new Reseller())->getKeyName());
    }

    #[CascadeDelete(true)]
    public function roles(): HasMany {
        return $this->hasMany(Role::class);
    }

    #[CascadeDelete(false)]
    public function audits(): HasMany {
        return $this->hasMany(Audit::class);
    }

    #[CascadeDelete(false)]
    public function users(): HasManyThrough {
        return $this->hasManyThrough(
            User::class,
            OrganizationUser::class,
            null,
            (new User())->getKeyName(),
            null,
            'user_id',
        );
    }

    #[CascadeDelete(true)]
    public function organizationUsers(): HasMany {
        return $this->hasMany(OrganizationUser::class);
    }
}
