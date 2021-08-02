<?php declare(strict_types = 1);

namespace App\Services\KeyCloak;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use App\Services\KeyCloak\Exceptions\AnotherUserExists;
use App\Services\KeyCloak\Exceptions\InsufficientData;
use App\Services\Organization\RootOrganization;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider as UserProviderContract;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Arr;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\UnencryptedToken;

use function array_filter;
use function array_keys;
use function array_unique;
use function array_values;

class UserProvider implements UserProviderContract {
    public const    CREDENTIAL_ACCESS_TOKEN     = 'access_token';
    public const    CREDENTIAL_PASSWORD         = 'password';
    public const    CREDENTIAL_EMAIL            = 'email';
    protected const CLAIM_RESOURCE_ACCESS       = 'resource_access';
    protected const CLAIM_RESELLER_ACCESS       = 'reseller_access';
    protected const CLAIM_EMAIL                 = 'email';
    protected const CLAIM_EMAIL_VERIFIED        = 'email_verified';
    protected const CLAIM_GIVEN_NAME            = 'given_name';
    protected const CLAIM_FAMILY_NAME           = 'family_name';
    protected const CLAIM_PHONE_NUMBER          = 'phone_number';
    protected const CLAIM_PHONE_NUMBER_VERIFIED = 'phone_number_verified';
    protected const CLAIM_PHOTO                 = 'photo';

    /**
     * @var array<string,array{property:string,required:boolean,default:mixed,if:string|null}>
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
    ];

    public function __construct(
        protected KeyCloak $keycloak,
        protected Jwt $jwt,
        protected Hasher $hasher,
        protected RootOrganization $rootOrganization,
    ) {
        // empty
    }

    // <editor-fold desc="Getters">
    // =========================================================================
    protected function getKeyCloak(): KeyCloak {
        return $this->keycloak;
    }

    protected function getJwt(): Jwt {
        return $this->jwt;
    }

    protected function getHasher(): Hasher {
        return $this->hasher;
    }

    public function getRootOrganization(): Organization {
        return $this->rootOrganization->get();
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
            $id   = $token->claims()->get(RegisteredClaims::SUBJECT);
            $user = User::query()->whereKey($id)->first();

            if ($user && $user->type !== UserType::keycloak()) {
                throw new AnotherUserExists($user);
            }

            if ($user) {
                $user = $this->updateTokenUser($user, $token);
            } else {
                $user = $this->createTokenUser($id, $token);
            }
        } elseif (isset($credentials[self::CREDENTIAL_EMAIL])) {
            $user = User::query()
                ->where('type', '=', UserType::local())
                ->where('email', '=', $credentials[self::CREDENTIAL_EMAIL])
                ->first();

            if ($user) {
                $user = $this->updateLocalUser($user);
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

    protected function updateTokenUser(User $user, UnencryptedToken $token): User {
        // Update properties
        foreach ($this->getProperties($token) as $property => $value) {
            $user->{$property} = $value;
        }

        // Save
        $user->save();

        // Return
        return $user;
    }

    protected function createTokenUser(string $id, UnencryptedToken $token): ?User {
        // Create
        $user                        = new User();
        $user->{$user->getKeyName()} = $id;
        $user->type                  = UserType::keycloak();

        // Update
        return $this->updateTokenUser($user, $token);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getProperties(UnencryptedToken $token): array {
        // Properties
        $claims     = $token->claims();
        $missed     = [];
        $properties = [];

        foreach ($this->map as $claim => $property) {
            if ($property['required'] && !$claims->has($claim)) {
                $missed[] = $claim;
            } elseif ($property['if'] === null || $claims->has($property['if'])) {
                $properties[$property['property']] = $claims->get($claim, $property['default']);
            } else {
                $properties[$property['property']] = null;
            }
        }

        // Organization
        $properties['organization'] = $this->getOrganization($token);

        // Permissions
        $properties['permissions'] = $this->getPermissions($token);

        // Sufficient?
        if ($missed) {
            throw new InsufficientData($missed);
        }

        // Return
        return $properties;
    }

    protected function getOrganization(UnencryptedToken $token): ?Organization {
        $organizations = $token->claims()->get(self::CLAIM_RESELLER_ACCESS, []);
        $organizations = array_filter(array_keys(array_filter($organizations)));
        $organization  = Organization::query()
            ->whereIn('keycloak_scope', $organizations)
            ->first();

        return $organization;
    }

    /**
     * @return array<string>
     */
    protected function getPermissions(UnencryptedToken $token): array {
        $roles = $token->claims()->get(self::CLAIM_RESOURCE_ACCESS, []);
        $roles = Arr::get($roles, "{$this->getKeyCloak()->getClientId()}.roles", []);
        $roles = array_unique(array_values($roles));

        return $roles;
    }

    protected function updateLocalUser(User $user): User {
        $user->organization = $this->getRootOrganization();
        $user->save();

        return $user;
    }
    // </editor-fold>
}
