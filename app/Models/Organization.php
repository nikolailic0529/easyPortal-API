<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Audits\Audit;
use App\Models\Data\Currency;
use App\Models\Enums\OrganizationType;
use App\Models\Relations\HasChangeRequests;
use App\Models\Relations\HasCurrency;
use App\Services\Audit\Contracts\Auditable;
use App\Services\Audit\Traits\AuditableImpl;
use App\Services\I18n\Contracts\HasTimezonePreference;
use App\Services\I18n\Eloquent\TranslatedString;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\OrganizationFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Organization.
 *
 * @property string                                 $id
 * @property string                                 $name
 * @property OrganizationType                       $type
 * @property string|null                            $keycloak_name
 * @property string|null                            $keycloak_scope
 * @property string|null                            $keycloak_group_id
 * @property string|null                            $locale
 * @property string|null                            $currency_id
 * @property string|null                            $website_url
 * @property string|null                            $email
 * @property string|null                            $timezone
 * @property string|null                            $analytics_code
 * @property bool|null                              $branding_dark_theme
 * @property string|null                            $branding_main_color
 * @property string|null                            $branding_secondary_color
 * @property string|null                            $branding_logo_url
 * @property string|null                            $branding_favicon_url
 * @property string|null                            $branding_default_main_color
 * @property string|null                            $branding_default_secondary_color
 * @property string|null                            $branding_default_logo_url
 * @property string|null                            $branding_default_favicon_url
 * @property string|null                            $branding_welcome_image_url
 * @property TranslatedString|null                  $branding_welcome_heading
 * @property TranslatedString|null                  $branding_welcome_underline
 * @property string|null                            $branding_dashboard_image_url
 * @property CarbonImmutable                        $created_at
 * @property CarbonImmutable                        $updated_at
 * @property CarbonImmutable|null                   $deleted_at
 * @property-read Collection<int, Audit>            $audits
 * @property-read Reseller|null                     $company
 * @property Currency|null                          $currency
 * @property-read Collection<int, OrganizationUser> $organizationUsers
 * @property-read Collection<int, Role>             $roles
 * @property-read Collection<int, User>             $users
 * @method static OrganizationFactory factory(...$parameters)
 * @method static Builder<Organization>|Organization newModelQuery()
 * @method static Builder<Organization>|Organization newQuery()
 * @method static Builder<Organization>|Organization query()
 */
class Organization extends Model implements
    HasLocalePreference,
    HasTimezonePreference,
    Auditable {
    use HasFactory;
    use AuditableImpl;
    use HasCurrency;
    use HasChangeRequests;

    /**
     * The attributes that should be cast to native types.
     *
     * @inheritdoc
     */
    protected $casts = [
        'type'                       => OrganizationType::class,
        'branding_dark_theme'        => 'bool',
        'branding_welcome_heading'   => TranslatedString::class,
        'branding_welcome_underline' => TranslatedString::class,
    ];

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'organizations';

    // <editor-fold desc="HasLocalePreference">
    // =========================================================================
    public function preferredLocale(): ?string {
        return $this->locale;
    }
    // </editor-fold>

    // <editor-fold desc="HasTimezonePreference">
    // =========================================================================
    public function preferredTimezone(): ?string {
        return $this->timezone;
    }
    // </editor-fold>

    // <editor-fold desc="Auditable">
    // =========================================================================
    /**
     * @inheritdoc
     */
    public function getInternalAttributes(): array {
        return [
            'keycloak_name',
            'keycloak_scope',
            'keycloak_group_id',
        ];
    }
    // </editor-fold>

    // <editor-fold desc="Relations">
    // =========================================================================
    /**
     * @return MorphTo<Reseller, Organization>
     */
    public function company(): MorphTo {
        return $this->morphTo(null, 'type', 'id');
    }

    /**
     * @return HasMany<Role>
     */
    public function roles(): HasMany {
        return $this->hasMany(Role::class);
    }

    /**
     * @return HasMany<Audit>
     */
    public function audits(): HasMany {
        return $this->hasMany(Audit::class);
    }

    /**
     * @return HasManyThrough<User>
     */
    public function users(): HasManyThrough {
        return $this
            ->hasManyThrough(
                User::class,
                OrganizationUser::class,
                null,
                (new User())->getKeyName(),
                null,
                'user_id',
            )
            ->distinct();
    }

    /**
     * @return HasMany<OrganizationUser>
     */
    public function organizationUsers(): HasMany {
        return $this->hasMany(OrganizationUser::class);
    }
    // </editor-fold>
}
