<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Enums\UserType;
use App\Services\Audit\Concerns\Auditable;
use App\Services\Auth\HasPermissions;
use App\Services\Auth\Rootable;
use App\Services\Organization\HasOrganization;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\RoutesNotifications;
use LogicException;

/**
 * User.
 *
 * @property string                                                                $id
 * @property \App\Models\Enums\UserType                                            $type
 * @property string|null                                                           $organization_id
 * @property string                                                                $given_name
 * @property string                                                                $family_name
 * @property string                                                                $email
 * @property bool                                                                  $email_verified
 * @property string|null                                                           $phone
 * @property bool|null                                                             $phone_verified
 * @property string|null                                                           $photo
 * @property array                                                                 $permissions
 * @property string|null                                                           $locale
 * @property string|null                                                           $password
 * @property string|null                                                           $homepage
 * @property string|null                                                           $timezone
 * @property \Carbon\CarbonImmutable                                               $created_at
 * @property \Carbon\CarbonImmutable                                               $updated_at
 * @property \Carbon\CarbonImmutable|null                                          $deleted_at
 * @property \App\Models\Organization|null                                         $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\UserSearch> $searches
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User query()
 * @mixin \Eloquent
 */
class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract,
    HasLocalePreference,
    HasOrganization,
    HasPermissions,
    Rootable,
    Auditable {
    use HasFactory;
    use Authenticatable;
    use Authorizable;
    use MustVerifyEmail;
    use CanResetPassword;
    use RoutesNotifications;

    protected const CASTS = [
        'type'           => UserType::class,
        'permissions'    => 'array',
        'email_verified' => 'bool',
        'phone_verified' => 'bool',
    ] + parent::CASTS;

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
    protected $casts = self::CASTS;

    // <editor-fold desc="MustVerifyEmail">
    // =========================================================================
    public function sendEmailVerificationNotification(): void {
        throw new LogicException('Email verification should be done inside standard process.');
    }
    // </editor-fold>

    // <editor-fold desc="HasLocalePreference">
    // =========================================================================
    public function preferredLocale(): ?string {
        return $this->locale;
    }
    // </editor-fold>

    // <editor-fold desc="Relations">
    // =========================================================================
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
    // </editor-fold>

    // <editor-fold desc="HasPermissions">
    // =========================================================================
    /**
     * @inheritDoc
     */
    public function getPermissions(): array {
        return $this->permissions ?? [];
    }
    // </editor-fold>

    // <editor-fold desc="HasOrganization">
    // =========================================================================
    public function isRoot(): bool {
        return $this->type === UserType::local();
    }
    // </editor-fold>
}
