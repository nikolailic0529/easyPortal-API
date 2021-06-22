<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasCurrency;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

use function app;

/**
 * Organization.
 *
 * @property string                                                             $id
 * @property string                                                             $name
 * @property string|null                                                        $keycloak_scope
 * @property string|null                                                        $keycloak_group_id
 * @property string|null                                                        $locale
 * @property string|null                                                        $currency_id
 * @property string|null                                                        $website_url
 * @property string|null                                                        $email
 * @property bool|null                                                          $branding_dark_theme
 * @property string|null                                                        $branding_main_color
 * @property string|null                                                        $branding_secondary_color
 * @property string|null                                                        $branding_logo_url
 * @property string|null                                                        $branding_favicon_url
 * @property string|null                                                        $branding_default_main_color
 * @property string|null                                                        $branding_default_secondary_color
 * @property string|null                                                        $branding_default_logo_url
 * @property string|null                                                        $branding_default_favicon_url
 * @property string|null                                                        $branding_welcome_image_url
 * @property string|null                                                        $branding_welcome_heading
 * @property string|null                                                        $branding_welcome_underline
 * @property string|null                                                        $analytics_code
 * @property string|null                                                        $timezone
 * @property \Carbon\CarbonImmutable                                            $created_at
 * @property \Carbon\CarbonImmutable                                            $updated_at
 * @property \Carbon\CarbonImmutable|null                                       $deleted_at
 * @property \App\Models\Currency|null                                          $currency
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Contact> $contacts
 * @property-read \App\Models\Location|null                                     $headquarter
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Location>     $locations
 * @property \App\Models\Status                                                 $status
 * @property \App\Models\Reseller                                               $reseller
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Role>         $roles
 * @method static \Database\Factories\OrganizationFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization query()
 * @mixin \Eloquent
 */
class Organization extends Model implements HasLocalePreference {
    use HasFactory;
    use HasCurrency;

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

    public function status(): HasOneThrough {
        return $this->hasOneThrough(Status::class, Reseller::class, 'id', 'id', 'id', 'status_id');
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
}
