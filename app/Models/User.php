<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\SyncBelongsToMany;
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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\RoutesNotifications;
use Illuminate\Support\Collection;
use LogicException;

/**
 * User.
 *
 * @property string                                                                  $id
 * @property \App\Models\Enums\UserType                                              $type
 * @property string|null                                                             $organization_id
 * @property string                                                                  $given_name
 * @property string                                                                  $family_name
 * @property string                                                                  $email
 * @property bool                                                                    $email_verified
 * @property string|null                                                             $phone
 * @property bool|null                                                               $phone_verified
 * @property string|null                                                             $photo
 * @property array                                                                   $permissions
 * @property string|null                                                             $locale
 * @property string|null                                                             $password
 * @property string|null                                                             $homepage
 * @property string|null                                                             $timezone
 * @property bool                                                                    $enabled
 * @property string|null                                                             $office_phone
 * @property string|null                                                             $contact_email
 * @property string|null                                                             $title
 * @property string|null                                                             $academic_title
 * @property string|null                                                             $mobile_phone
 * @property string|null                                                             $department
 * @property string|null                                                             $job_title
 * @property string|null                                                             $company
 * @property \Carbon\CarbonImmutable                                                 $created_at
 * @property \Carbon\CarbonImmutable                                                 $updated_at
 * @property \Carbon\CarbonImmutable|null                                            $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Invitation>   $invitations
 * @property \App\Models\Organization|null                                           $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\UserSearch>   $searches
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Organization> $organizations
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Role>         $roles
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Team>         $teams
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
    use SyncBelongsToMany;

    protected const CASTS = [
        'type'           => UserType::class,
        'permissions'    => 'array',
        'email_verified' => 'bool',
        'phone_verified' => 'bool',
        'enabled'        => 'bool',
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
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $visible = [
        'given_name',
        'family_name',
        'email',
        'email_verified',
        'phone',
        'phone_verified',
        'photo',
        'locale',
        'homepage',
        'timezone',
        'created_at',
        'updated_at',
        'deleted_at',
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

    public function organizations(): BelongsToMany {
        $pivot = new OrganizationUser();

        return $this
            ->belongsToMany(Organization::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Organization>|array<\App\Models\Organization> $organizations
     */
    public function setOrganizationsAttribute(Collection|array $organizations): void {
        $this->syncBelongsToMany('organizations', $organizations);
    }

    public function roles(): BelongsToMany {
        $pivot = new UserRole();

        return $this
            ->belongsToMany(Role::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Role>|array<\App\Models\Role> $roles
     */
    public function setRolesAttribute(Collection|array $roles): void {
        $this->syncBelongsToMany('roles', $roles);
    }

    public function invitations(): HasMany {
        return $this->hasMany(Invitation::class);
    }

    public function teams(): BelongsToMany {
        $pivot = new OrganizationUser();

        return $this
            ->belongsToMany(Team::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Team>|array<\App\Models\Team> $teams
     */
    public function setTeamsAttribute(Collection|array $teams): void {
        $this->syncBelongsToMany('teams', $teams);
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
    /**
     * Must not be used directly to check root. You must use
     * {@link \App\Services\Auth\Auth::isRoot()} instead.
     */
    public function isRoot(): bool {
        return $this->type === UserType::local();
    }
    // </editor-fold>
}
