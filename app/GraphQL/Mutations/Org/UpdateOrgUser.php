<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\GraphQL\Mutations\Me\UpdateMeProfile;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User as KeycloakUser;
use App\Services\Organization\CurrentOrganization;

use function array_key_exists;

class UpdateOrgUser {
    public function __construct(
        protected Client $client,
        protected CurrentOrganization $organization,
        protected UpdateMeProfile $updateMeProfile,
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
        $this->updateMeProfile->updateUserProfile($user, $keycloakUser, $this->getProfileValues($input));

        // Update Settings
        $this->settings($user, $input);

        // OrganizationUser
        $organization_user = OrganizationUser::query()
            ->where('organization_id', '=', $organization->getKey())
            ->where('user_id', '=', $user->getKey())
            ->first();

        if (!$organization_user) {
            $organization_user                  = new OrganizationUser();
            $organization_user->organization_id = $organization->getKey();
            $organization_user->user_id         = $user->getKey();
        }

        // Update Role
        if (isset($input['role_id']) && !$user->isRoot()) {
            $role = Role::query()->whereKey($input['role_id'])->first();
            $this->role($organization_user, $keycloakUser, $role);
        }

        // Update Team
        if (isset($input['team_id'])) {
            $organization_user->team_id = $input['team_id'];
        }

        $organization_user->save();

        return ['result' => $user->save()];
    }

    /**
     * @param array<string, mixed> $input
     */
    protected function settings(User $user, array $input): void {
        if (array_key_exists('locale', $input)) {
            $user->locale = $input['locale'];
        }

        if (array_key_exists('homepage', $input)) {
            $user->homepage = $input['homepage'];
        }

        if (array_key_exists('timezone', $input)) {
            $user->timezone = $input['timezone'];
        }
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
    protected function getProfileValues(array $input): array {
        $attributes = [
            'first_name',
            'last_name',
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
