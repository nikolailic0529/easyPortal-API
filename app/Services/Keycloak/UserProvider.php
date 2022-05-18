<?php declare(strict_types = 1);

namespace App\Services\Keycloak;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Permission;
use App\Models\User;
use App\Services\Auth\Auth;
use App\Services\Auth\Concerns\AvailablePermissions;
use App\Services\Keycloak\Exceptions\Auth\AnotherUserExists;
use App\Services\Keycloak\Exceptions\Auth\UserDisabled;
use App\Services\Keycloak\Exceptions\Auth\UserInsufficientData;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Services\Organization\RootOrganization;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider as UserProviderContract;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\UnencryptedToken;

use function array_filter;
use function array_keys;
use function sprintf;

class UserProvider implements UserProviderContract {
    use AvailablePermissions;

    public const    CREDENTIAL_ACCESS_TOKEN     = 'access_token';
    public const    CREDENTIAL_ORGANIZATION     = 'organization';
    public const    CREDENTIAL_PASSWORD         = 'password';
    public const    CREDENTIAL_EMAIL            = 'email';
    protected const CLAIM_RESELLER_ACCESS       = 'reseller_access';
    protected const CLAIM_EMAIL                 = 'email';
    protected const CLAIM_EMAIL_VERIFIED        = 'email_verified';
    protected const CLAIM_GIVEN_NAME            = 'given_name';
    protected const CLAIM_FAMILY_NAME           = 'family_name';
    protected const CLAIM_PHONE_NUMBER          = 'phone_number';
    protected const CLAIM_PHONE_NUMBER_VERIFIED = 'phone_number_verified';
    protected const CLAIM_PHOTO                 = 'photo';
    protected const CLAIM_ENABLED               = 'enabled';
    protected const CLAIM_JOB_TITLE             = 'job_title';
    protected const CLAIM_MOBILE_PHONE          = 'mobile_phone';
    protected const CLAIM_OFFICE_PHONE          = 'office_phone';
    protected const CLAIM_TITLE                 = 'title';
    protected const CLAIM_CONTACT_EMAIL         = 'contact_email';
    protected const CLAIM_ACADEMIC_TITLE        = 'academic_title';
    protected const CLAIM_LOCALE                = 'locale';
    protected const CLAIM_HOMEPAGE              = 'homepage';

    /**
     * @var array<string,array{property:string,required:boolean,default:mixed,if:string|null,map:callable}>
     */
    protected array $map = [
        self::CLAIM_EMAIL                 => [
            'property' => 'email',
            'required' => true,
            'default'  => null,
            'if'       => null,
        ],
        self::CLAIM_EMAIL_VERIFIED        => [
            'property' => 'email_verified',
            'required' => false,
            'default'  => false,
            'if'       => null,
        ],
        self::CLAIM_GIVEN_NAME            => [
            'property' => 'given_name',
            'required' => true,
            'default'  => null,
            'if'       => null,
        ],
        self::CLAIM_FAMILY_NAME           => [
            'property' => 'family_name',
            'required' => true,
            'default'  => null,
            'if'       => null,
        ],
        self::CLAIM_PHONE_NUMBER          => [
            'property' => 'phone',
            'required' => false,
            'default'  => null,
            'if'       => null,
        ],
        self::CLAIM_PHONE_NUMBER_VERIFIED => [
            'property' => 'phone_verified',
            'required' => false,
            'default'  => null,
            'if'       => self::CLAIM_PHONE_NUMBER,
        ],
        self::CLAIM_PHOTO                 => [
            'property' => 'photo',
            'required' => false,
            'default'  => null,
            'if'       => null,
        ],
        self::CLAIM_ENABLED               => [
            'property' => 'enabled',
            'required' => false,
            'default'  => false,
            'if'       => null,
        ],
        self::CLAIM_JOB_TITLE             => [
            'property' => 'job_title',
            'required' => false,
            'default'  => null,
            'if'       => null,
        ],
        self::CLAIM_MOBILE_PHONE          => [
            'property' => 'mobile_phone',
            'required' => false,
            'default'  => null,
            'if'       => null,
        ],
        self::CLAIM_OFFICE_PHONE          => [
            'property' => 'office_phone',
            'required' => false,
            'default'  => null,
            'if'       => null,
        ],
        self::CLAIM_TITLE                 => [
            'property' => 'title',
            'required' => false,
            'default'  => null,
            'if'       => null,
        ],
        self::CLAIM_CONTACT_EMAIL         => [
            'property' => 'contact_email',
            'required' => false,
            'default'  => null,
            'if'       => null,
        ],
        self::CLAIM_ACADEMIC_TITLE        => [
            'property' => 'academic_title',
            'required' => false,
            'default'  => null,
            'if'       => null,
        ],
        self::CLAIM_LOCALE                => [
            'property' => 'locale',
            'required' => false,
            'default'  => null,
            'if'       => null,
            'map'      => [Map::class, 'getAppLocale'],
        ],
        self::CLAIM_HOMEPAGE              => [
            'property' => 'homepage',
            'required' => false,
            'default'  => null,
            'if'       => null,
        ],
    ];

    public function __construct(
        protected Jwt $jwt,
        protected Hasher $hasher,
        protected RootOrganization $rootOrganization,
        protected Auth $auth,
    ) {
        // empty
    }

    // <editor-fold desc="Getters">
    // =========================================================================
    protected function getJwt(): Jwt {
        return $this->jwt;
    }

    protected function getHasher(): Hasher {
        return $this->hasher;
    }

    public function getRootOrganization(): RootOrganization {
        return $this->rootOrganization;
    }

    protected function getAuth(): Auth {
        return $this->auth;
    }
    // </editor-fold>

    // <editor-fold desc="UserProvider">
    // =========================================================================
    /**
     * @inheritDoc
     */
    public function retrieveById($identifier): User|null {
        return User::query()
            ->whereKey($identifier)
            ->first();
    }

    /**
     * @inheritDoc
     */
    public function retrieveByToken($identifier, $token): User|null {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function updateRememberToken(Authenticatable $user, $token) {
        // no action
    }

    /**
     * @inheritDoc
     */
    public function retrieveByCredentials(array $credentials): User|null {
        $user  = null;
        $token = $this->getToken($credentials);

        if ($token instanceof UnencryptedToken) {
            $id           = $token->claims()->get(RegisteredClaims::SUBJECT);
            $user         = User::query()->whereKey($id)->first();
            $organization = $credentials[self::CREDENTIAL_ORGANIZATION] ?? null;

            if ($organization && !($organization instanceof Organization)) {
                throw new InvalidArgumentException(sprintf(
                    'The `%s` must be `null` or instance of `%s`.',
                    self::CREDENTIAL_ORGANIZATION,
                    Organization::class,
                ));
            }

            if ($user && $user->type !== UserType::keycloak()) {
                throw new AnotherUserExists($user);
            }

            if ($user) {
                $user = $this->updateTokenUser($user, $token, $organization);
            } else {
                $user = $this->createTokenUser($id, $token, $organization);
            }
        } elseif (isset($credentials[self::CREDENTIAL_EMAIL])) {
            $user = User::query()
                ->where('type', '=', UserType::local())
                ->where('email', '=', $credentials[self::CREDENTIAL_EMAIL])
                ->first();

            if ($user) {
                $user = $this->updateLocalUser($user);
            }

            if ($user && !$user->isEnabled(null)) {
                throw new UserDisabled($user);
            }
        } else {
            // empty
        }

        return $user;
    }

    /**
     * @inheritDoc
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool {
        // User?
        if (!($user instanceof User)) {
            return false;
        }

        // Validate
        $valid = false;
        $token = $this->getToken($credentials);

        if ($token instanceof UnencryptedToken) {
            $valid = $token->isRelatedTo($user->getAuthIdentifier());
        } elseif (isset($credentials[self::CREDENTIAL_PASSWORD])) {
            $valid = $this->getHasher()->check($credentials[self::CREDENTIAL_PASSWORD], $user->getAuthPassword());
        } else {
            // empty
        }

        // Return
        return $valid;
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @param array<mixed> $credentials
     */
    protected function getToken(array $credentials): ?Token {
        $token = null;

        if (isset($credentials[self::CREDENTIAL_ACCESS_TOKEN])) {
            $token = $this->getJwt()->decode($credentials[self::CREDENTIAL_ACCESS_TOKEN]);
        }

        return $token;
    }

    protected function updateTokenUser(User $user, UnencryptedToken $token, ?Organization $organization): User {
        // Update properties
        foreach ($this->getProperties($user, $token, $organization) as $property => $value) {
            $user->setAttribute($property, $value);
        }

        // Save
        $user->save();

        // Return
        return $user;
    }

    protected function createTokenUser(string $id, UnencryptedToken $token, ?Organization $organization): ?User {
        // Create
        $user       = new User();
        $user->id   = $id;
        $user->type = UserType::keycloak();

        // Update
        return $this->updateTokenUser($user, $token, $organization);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getProperties(User $user, UnencryptedToken $token, ?Organization $organization): array {
        // Properties
        $claims     = $token->claims();
        $missed     = [];
        $properties = [];

        foreach ($this->map as $claim => $property) {
            if ($property['required'] && !$claims->has($claim)) {
                $missed[] = $claim;
            } elseif ($property['if'] === null || $claims->has($property['if'])) {
                $value                             = $claims->get($claim, $property['default']);
                $properties[$property['property']] = $value !== null && isset($property['map'])
                    ? $property['map']($value)
                    : $value;
            } else {
                $properties[$property['property']] = null;
            }
        }

        // Organization
        $organization               = $this->getOrganization($user, $token, $organization);
        $properties['organization'] = $organization;

        // Permissions
        $properties['permissions'] = $this->getPermissions($user, $token, $organization);

        // Sufficient?
        if ($missed) {
            throw new UserInsufficientData($user, $missed);
        }

        // Return
        return $properties;
    }

    protected function getOrganization(
        User $user,
        UnencryptedToken $token,
        ?Organization $organization,
    ): ?Organization {
        return GlobalScopes::callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            static function () use ($user, $token, $organization): ?Organization {
                $organizations = $token->claims()->get(self::CLAIM_RESELLER_ACCESS, []);
                $organizations = array_filter(array_keys(array_filter($organizations)));
                $organization  = Organization::query()
                    ->whereIn('keycloak_scope', $organizations)
                    ->when($organization, static function (Builder $builder, Organization $organization): Builder {
                        return $builder->whereKey($organization->getKey());
                    })
                    ->first();
                $isMember      = false;

                if ($organization) {
                    $isMember = $user->organizations
                        ->contains(static function (OrganizationUser $user) use ($organization): bool {
                            return $user->organization_id === $organization->getKey();
                        });
                }

                if (!$isMember) {
                    $organization = null;
                }

                return $organization;
            },
        );
    }

    /**
     * @return array<string>
     */
    protected function getPermissions(User $user, UnencryptedToken $token, ?Organization $organization): array {
        return GlobalScopes::callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            function () use ($user, $organization): array {
                // Organization is required
                if (!$organization) {
                    return [];
                }

                // Member of Organization?
                /** @var OrganizationUser|null $member */
                $member = $user->organizations
                    ->first(static function (OrganizationUser $user) use ($organization): bool {
                        return $user->organization_id === $organization->getKey()
                            && $user->enabled;
                    });
                $role   = $member?->role;

                if (!$role) {
                    return [];
                }

                // Available permissions
                $available   = $this->getAvailablePermissions($organization);
                $permissions = $role->permissions
                    ->map(static function (Permission $permission): string {
                        return $permission->key;
                    })
                    ->intersect($available)
                    ->unique()
                    ->values()
                    ->all();

                // Return
                return $permissions;
            },
        );
    }

    protected function updateLocalUser(User $user): User {
        $user->organization = $this->getRootOrganization()->get();
        $user->save();

        return $user;
    }
    // </editor-fold>
}
