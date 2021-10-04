<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\GraphQL\Mutations\Me\UpdateMe;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\Auth;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User as KeycloakUser;
use App\Services\Organization\CurrentOrganization;

use function array_key_exists;

class UpdateOrgUser {
    public function __construct(
        protected Auth $auth,
        protected Client $client,
        protected CurrentOrganization $organization,
        protected UpdateMe $updateMe,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        $organization = $this->organization->get();
        $input        = $args['input'];
        $user         = User::query()->whereKey($input['user_id'])->first();

        if (!($user instanceof User)) {
            return [
                'result' => false,
            ];
        }

        $keycloakUser = $this->client->getUserById($user->getKey());

        // Update Profile
        $this->updateMe->updateUser($user, $keycloakUser, $this->getMeValues($input));

        // OrganizationUser
        $organizationUser = OrganizationUser::query()
            ->where('organization_id', '=', $organization->getKey())
            ->where('user_id', '=', $user->getKey())
            ->first();

        if (!$organizationUser) {
            $organizationUser                  = new OrganizationUser();
            $organizationUser->organization_id = $organization->getKey();
            $organizationUser->user_id         = $user->getKey();
        }

        // Update Role
        if (isset($input['role_id']) && !$this->auth->isRoot($user)) {
            $role = Role::query()->whereKey($input['role_id'])->first();
            $this->role($organizationUser, $keycloakUser, $role);
        }

        // Update Team
        if (isset($input['team_id'])) {
            $organizationUser->team_id = $input['team_id'];
        }

        $organizationUser->save();

        return ['result' => $user->save()];
    }

    protected function role(
        OrganizationUser $organizationUser,
        KeycloakUser $keycloakUser,
        Role $role,
    ): void {
        if ($organizationUser->role_id) {
            // remove organization old role from keycloak
            $this->client->removeUserFromGroup($keycloakUser->id, $organizationUser->role_id);
        }
        // Add new role
        $this->client->addUserToGroup($keycloakUser->id, $role->getKey());
        $organizationUser->role_id = $role->getKey();
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string>
     */
    protected function getMeValues(array $input): array {
        $attributes = [
            'given_name',
            'family_name',
            'office_phone',
            'contact_email',
            'title',
            'academic_title',
            'mobile_phone',
            'department',
            'job_title',
            'phone',
            'company',
            'photo',
            'homepage',
            'locale',
            'timezone',
        ];
        $result     = [];
        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $input)) {
                $result[$attribute] = $input[$attribute];
            }
        }

        return $result;
    }
}
