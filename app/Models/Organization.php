<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Audits\Audit;
use App\Models\Concerns\Relations\HasCurrency;
use App\Services\Audit\Concerns\Auditable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

use function app;

/**
 * Organization.
 *
 * @property string                                                                  $id
 * @property string                                                                  $name
 * @property string|null                                                             $keycloak_scope
 * @property string|null                                                             $keycloak_group_id
 * @property string|null                                                             $locale
 * @property string|null                                                             $currency_id
 * @property string|null                                                             $website_url
 * @property string|null                                                             $email
 * @property int                                                                     $kpi_assets_total
 * @property int                                                                     $kpi_assets_active
 * @property float                                                                   $kpi_assets_covered
 * @property int                                                                     $kpi_customers_active
 * @property int                                                                     $kpi_customers_active_new
 * @property int                                                                     $kpi_contracts_active
 * @property float                                                                   $kpi_contracts_active_amount
 * @property int                                                                     $kpi_contracts_active_new
 * @property int                                                                     $kpi_contracts_expiring
 * @property int                                                                     $kpi_quotes_active
 * @property float                                                                   $kpi_quotes_active_amount
 * @property int                                                                     $kpi_quotes_active_new
 * @property int                                                                     $kpi_quotes_expiring
 * @property bool|null                                                               $branding_dark_theme
 * @property string|null                                                             $branding_main_color
 * @property string|null                                                             $branding_secondary_color
 * @property string|null                                                             $branding_logo_url
 * @property string|null                                                             $branding_favicon_url
 * @property string|null                                                             $branding_default_main_color
 * @property string|null                                                             $branding_default_secondary_color
 * @property string|null                                                             $branding_default_logo_url
 * @property string|null                                                             $branding_default_favicon_url
 * @property string|null                                                             $branding_welcome_image_url
 * @property string|null                                                             $branding_welcome_heading
 * @property string|null                                                             $branding_welcome_underline
 * @property string|null                                                             $analytics_code
 * @property string|null                                                             $timezone
 * @property \Carbon\CarbonImmutable                                                 $created_at
 * @property \Carbon\CarbonImmutable                                                 $updated_at
 * @property \Carbon\CarbonImmutable|null                                            $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Audits\Audit> $audits
 * @property \App\Models\Currency|null                                               $currency
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Contact>      $contacts
 * @property-read \App\Models\Location|null                                          $headquarter
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Location>     $locations
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Status>       $statuses
 * @property-read \App\Models\Reseller                                               $reseller
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Role>         $roles
 * @method static \Database\Factories\OrganizationFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization query()
 * @mixin \Eloquent
 */
class Organization extends Model implements
    HasLocalePreference,
    Auditable {
    use HasFactory;
    use HasCurrency;

    protected const CASTS = [
        'branding_dark_theme' => 'bool',
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

    public function statuses(): BelongsToMany {
        $pivot = new ResellerStatus();

        return $this
            ->belongsToMany(Status::class, $pivot->getTable(), 'reseller_id')
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    public function contacts(): HasManyThrough {
        [$type, $id] = $this->getMorphs('object', null, null);

        return $this->hasManyThrough(Contact::class, Reseller::class, 'id', $id)
            ->where($type, '=', (new Reseller())->getMorphClass());
    }

    public function locations(): HasManyThrough {
        [$type, $id] = $this->getMorphs('object', null, null);

        return $this->hasManyThrough(Location::class, Reseller::class, 'id', $id)
            ->where($type, '=', (new Reseller())->getMorphClass());
    }

    public function headquarter(): HasOneThrough {
        $type             = app()->make(Repository::class)->get('ep.headquarter_type');
        [$morphType, $id] = $this->getMorphs('object', null, null);

        return $this
            ->hasOneThrough(Location::class, Reseller::class, 'id', $id)
            ->where($morphType, '=', (new Reseller())->getMorphClass())
            ->whereHas('types', static function ($query) use ($type) {
                return $query->whereKey($type);
            });
    }

    public function reseller(): HasOne {
        return $this->hasOne(Reseller::class, (new Reseller())->getKeyName());
    }

    public function roles(): HasMany {
        return $this->hasMany(Role::class);
    }

    public function audits(): HasMany {
        return $this->hasMany(Audit::class);
    }

    public function users(): BelongsToMany {
        $pivot = new OrganizationUser();

        return $this
            ->belongsToMany(User::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }
}
