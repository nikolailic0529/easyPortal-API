<?php declare(strict_types = 1);

namespace App\Models;

use App\Services\Organization\HasOrganization;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use LogicException;

/**
 * User.
 *
 * @property string                                                                $id
 * @property string|null                                                           $organization_id
 * @property string                                                                $given_name
 * @property string                                                                $family_name
 * @property string                                                                $email
 * @property bool                                                                  $email_verified
 * @property string                                                                $phone
 * @property bool                                                                  $phone_verified
 * @property string|null                                                           $photo
 * @property array                                                                 $permissions
 * @property string|null                                                           $locale
 * @property \Carbon\CarbonImmutable                                               $created_at
 * @property \Carbon\CarbonImmutable                                               $updated_at
 * @property \Carbon\CarbonImmutable|null                                          $deleted_at
 * @property \App\Models\Organization                                              $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\UserSearch> $searches
 * @property-read int|null                                                         $searches_count
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereEmailVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereFamilyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereGivenName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User wherePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User wherePhoneVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    HasLocalePreference,
    HasOrganization {
    use HasFactory;
    use Authenticatable;
    use Authorizable;
    use MustVerifyEmail;

    protected const CASTS = [
        'permissions'    => 'array',
        'email_verified' => 'bool',
        'phone_verified' => 'bool',
    ];

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'email',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $hidden = [
        // Not used, just for case.
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS + parent::CASTS;

    public function sendEmailVerificationNotification(): void {
        throw new LogicException('Email verification should be done inside standard process.');
    }

    public function preferredLocale(): ?string {
        return $this->locale;
    }

    public function searches(): HasMany {
        return $this->hasMany(UserSearch::class);
    }

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }

    public function setOrganizationAttribute(?Organization $organization): void {
        $this->organization()->associate($organization);
    }

    public function getOrganization(): ?Organization {
        return $this->organization;
    }
}
