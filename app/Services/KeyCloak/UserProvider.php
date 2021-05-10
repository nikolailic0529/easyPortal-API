<?php declare(strict_types = 1);

namespace App\Services\KeyCloak;

use App\Models\Organization;
use App\Models\User;
use App\Services\KeyCloak\Exceptions\InsufficientData;
use App\Services\Organization\HasOrganization;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider as UserProviderContract;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\UnencryptedToken;

use function array_filter;
use function array_keys;

class UserProvider implements UserProviderContract {
    public const    ACCESS_TOKEN                = 'access_token';
    protected const CLAIM_RESELLER_ACCESS       = 'reseller_access';
    protected const CLAIM_EMAIL                 = 'email';
    protected const CLAIM_EMAIL_VERIFIED        = 'email_verified';
    protected const CLAIM_GIVEN_NAME            = 'given_name';
    protected const CLAIM_FAMILY_NAME           = 'family_name';
    protected const CLAIM_PHONE_NUMBER          = 'phone_number';
    protected const CLAIM_PHONE_NUMBER_VERIFIED = 'phone_number_verified';
    protected const CLAIM_LOCALE                = 'locale';

    /**
     * @var array<string,string>
     */
    protected array $map = [
        self::CLAIM_EMAIL                 => [
            'property' => 'email',
            'required' => true,
            'default'  => null,
        ],
        self::CLAIM_EMAIL_VERIFIED        => [
            'property' => 'email_verified',
            'required' => false,
            'default'  => false,
        ],
        self::CLAIM_GIVEN_NAME            => [
            'property' => 'given_name',
            'required' => true,
            'default'  => null,
        ],
        self::CLAIM_FAMILY_NAME           => [
            'property' => 'family_name',
            'required' => true,
            'default'  => null,
        ],
        self::CLAIM_PHONE_NUMBER          => [
            'property' => 'phone',
            'required' => true,
            'default'  => null,
        ],
        self::CLAIM_PHONE_NUMBER_VERIFIED => [
            'property' => 'phone_verified',
            'required' => false,
            'default'  => false,
        ],
        self::CLAIM_LOCALE                => [
            'property' => 'locale',
            'required' => false,
            'default'  => null,
        ],
    ];

    public function __construct(
        protected KeyCloak $keycloak,
        protected Jwt $jwt,
    ) {
        // empty
    }

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

            if ($user) {
                $user = $this->update($user, $token);
            } else {
                $user = $this->create($id, $token);
            }
        }

        return $user;
    }

    /**
     * @inheritDoc
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool {
        $valid = false;
        $token = $this->getToken($credentials);

        if ($token instanceof UnencryptedToken) {
            $valid = $token->isRelatedTo($user->getAuthIdentifier());
        }

        return $valid;
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    protected function getKeyCloak(): KeyCloak {
        return $this->keycloak;
    }

    protected function getJwt(): Jwt {
        return $this->jwt;
    }

    /**
     * @param array<mixed> $credentials
     */
    protected function getToken(array $credentials): ?Token {
        $token = null;

        if (isset($credentials[self::ACCESS_TOKEN])) {
            $token = $this->getJwt()->decode($credentials[self::ACCESS_TOKEN]);
        }

        return $token;
    }

    protected function update(User $user, UnencryptedToken $token): User {
        // Update properties
        foreach ($this->getProperties($token) as $property => $value) {
            $user->{$property} = $value;
        }

        // Save
        $user->save();

        // Return
        return $user;
    }

    protected function create(string $id, UnencryptedToken $token): ?User {
        // Create
        $user                        = new User();
        $user->{$user->getKeyName()} = $id;

        // Update
        return $this->update($user, $token);
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
            } else {
                $properties[$property['property']] = $claims->get($claim, $property['default']);
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
        // FIXME [KeyCloak] Permissions

        return [];
    }
    // </editor-fold>
}
