<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Enums\UserType;
use App\Services\Audit\Concerns\Auditable;
use App\Services\Auth\Contracts\Enableable;
use App\Services\Auth\Contracts\HasPermissions;
use App\Services\Auth\Contracts\Rootable;
use App\Services\I18n\Contracts\HasTimezonePreference;
use App\Services\Organization\Eloquent\OwnedByScope;
use App\Services\Organization\HasOrganization;
use App\Utils\Eloquent\Concerns\SyncBelongsToMany;
use App\Utils\Eloquent\Concerns\SyncHasMany;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\RoutesNotifications;
use Illuminate\Support\Collection as BaseCollection;
use LogicException;

/**
 * User.
 *
 * @property string                            $id
 * @property UserType                          $type
 * @property string|null                       $organization_id
 * @property string|null                       $given_name
 * @property string|null                       $family_name
 * @property string                            $email
 * @property bool                              $email_verified
 * @property string|null                       $phone
 * @property bool|null                         $phone_verified
 * @property string|null                       $photo
 * @property array                             $permissions
 * @property string|null                       $locale
 * @property string|null                       $password
 * @property string|null                       $homepage
 * @property string|null                       $timezone
 * @property bool                              $enabled
 * @property string|null                       $office_phone
 * @property string|null                       $contact_email
 * @property string|null                       $title
 * @property string|null                       $academic_title
 * @property string|null                       $mobile_phone
 * @property string|null                       $job_title
 * @property string|null                       $company
 * @property CarbonInterface|null              $previous_sign_in
 * @property string|null                       $freshchat_id
 * @property CarbonInterface|null              $synced_at
 * @property CarbonImmutable                   $created_at
 * @property CarbonImmutable                   $updated_at
 * @property CarbonImmutable|null              $deleted_at
 * @property-read Collection<int, Invitation>  $invitations
 * @property Organization|null                 $organization
 * @property Collection<int, OrganizationUser> $organizations
 * @property-read Collection<int, UserSearch>  $searches
 * @method static UserFactory factory(...$parameters)
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 */
class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract,
    HasLocalePreference,
    HasTimezonePreference,
    HasOrganization,
    HasPermissions,
    Enableable,
    Rootable,
    Auditable {
    use HasFactory;
    use Authenticatable;
    use Authorizable;
    use MustVerifyEmail;
    use CanResetPassword;
    use RoutesNotifications;
    use SyncBelongsToMany;
    use SyncHasMany;

    protected const CASTS = [
        'type'             => UserType::class,
        'permissions'      => 'array',
        'email_verified'   => 'bool',
        'phone_verified'   => 'bool',
        'enabled'          => 'bool',
        'synced_at'        => 'datetime',
        'previous_sign_in' => 'datetime',
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
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $hidden = [
        // Not used, just for case.
        'password',
        'remember_token',
    ];

    /**
     * @var list<string>
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
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
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

    // <editor-fold desc="HasTimezonePreference">
    // =========================================================================
    public function preferredTimezone(): ?string {
        return $this->timezone;
    }
    // </editor-fold>

    // <editor-fold desc="Relations">
    // =========================================================================
    /**
     * @return HasMany<UserSearch>
     */
    public function searches(): HasMany {
        return $this->hasMany(UserSearch::class);
    }

    /**
     * @return BelongsTo<Organization, User>
     */
    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }

    public function setOrganizationAttribute(?Organization $organization): void {
        $this->organization()->associate($organization);
    }

    /**
     * @return HasMany<Invitation>
     */
    public function invitations(): HasMany {
        return $this->hasMany(Invitation::class);
    }

    /**
     * @return HasMany<OrganizationUser>
     */
    public function organizations(): HasMany {
        return $this->hasMany(OrganizationUser::class);
    }

    /**
     * @param Collection<int,OrganizationUser> $organizations
     */
    public function setOrganizationsAttribute(Collection $organizations): void {
        $this->syncHasMany('organizations', $organizations);
    }
    // </editor-fold>

    // <editor-fold desc="HasOrganization">
    // =========================================================================
    public function getOrganization(): ?Organization {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): bool {
        $this->organization = $organization;
        $result             = $this->save();

        return $result;
    }

    public function getOrganizations(): BaseCollection {
        return $this->organizations
            ->loadMissing('organization')
            ->filter(function (OrganizationUser $user): bool {
                return $this->isEnabled($user->organization);
            })
            ->map(static function (OrganizationUser $user): Organization {
                return $user->organization;
            });
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

    /**
     * @inheritDoc
     */
    public function setPermissions(array $permissions): bool {
        $this->permissions = $permissions;
        $result            = $this->save();

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getOrganizationPermissions(Organization $organization): array {
        // Organization?
        if (!$this->isEnabled($organization)) {
            return [];
        }

        // Member of Organization?
        /** @var OrganizationUser|null $member */
        $member = $this->organizations
            ->first(static function (OrganizationUser $user) use ($organization): bool {
                return $user->organization_id === $organization->getKey();
            });
        $role   = $member?->role;

        if (!$role) {
            return [];
        }

        // Permissions
        $permissions = $role->permissions
            ->map(static function (Permission $permission): string {
                return $permission->key;
            })
            ->unique()
            ->values()
            ->all();

        // Return
        return $permissions;
    }
    // </editor-fold>

    // <editor-fold desc="Rootable">
    // =========================================================================
    /**
     * Must not be used directly to check root! You must use
     * {@link \App\Services\Auth\Auth::isRoot()} instead.
     */
    public function isRoot(): bool {
        return $this->type === UserType::local();
    }
    // </editor-fold>

    // <editor-fold desc="Enableable">
    // =========================================================================
    public function isEnabled(?Organization $organization): bool {
        // Enabled?
        if (!$this->enabled || !$this->email_verified) {
            return false;
        }

        // Global?
        if ($organization === null) {
            return true;
        }

        // Root?
        if ($this->isRoot()) {
            return true;
        }

        // Member of organization?
        return GlobalScopes::callWithout(
            OwnedByScope::class,
            function () use ($organization): bool {
                $orgUser = $this->organizations
                    ->first(static function (OrganizationUser $user) use ($organization): bool {
                        return $user->organization_id === $organization->getKey()
                            && $user->invited === false;
                    });

                return $orgUser instanceof OrganizationUser
                    && $orgUser->enabled;
            },
        );
    }
    // </editor-fold>
}
