<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\GraphQL\Mutations\Me\UpdateMeProfile;
use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User as KeycloakUser;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Auth\AuthManager;
use Nuwave\Lighthouse\Exceptions\AuthorizationException;

use function array_key_exists;

class UpdateOrgUser {
    public function __construct(
        protected AuthManager $auth,
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
        $currentUser  = $this->auth->user();

        if (!($user instanceof User)) {
            return [
                'result' => false,
            ];
        }

        if ($user->isRoot() && !$currentUser->isRoot()) {
            throw new AuthorizationException();
        }

        $keycloakUser = $this->client->getUserById($user->getKey());

        // Update Profile
        $this->updateMeProfile->updateUserProfile($user, $keycloakUser, $input);

        // Update Settings
        $this->settings($user, $input);

        // Update Role
        if (isset($input['role_id']) && $user->type === UserType::keycloak()) {
            $role = Role::query()->whereKey($input['role_id'])->first();
            $this->role($organization, $user, $keycloakUser, $role);
        }

        // Update Team
        if (isset($input['team_id'])) {
            $pivot = OrganizationUser::query()
                ->where('organization_id', '=', $organization->getKey())
                ->where('user_id', '=', $user->getKey())
                ->first();

            if (!$pivot) {
                $pivot                  = new OrganizationUser();
                $pivot->organization_id = $organization->getKey();
                $pivot->user_id         = $user->getKey();
            }

            $pivot->team_id = $input['team_id'];
            $pivot->save();
        }
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
        Organization $organization,
        User $user,
        KeycloakUser $keycloakUser,
        Role $role,
    ): void {
        $query       = $user->roles()
            ->where((new Role())->qualifyColumn('organization_id'), '=', $organization->getKey());
        $currentRole = $query->first();
        if ($currentRole) {
            // remove organization old role from keycloak
            $this->client->removeUserFromGroup($keycloakUser->id, $currentRole->getKey());
            $query->detach();
        }
        // Add new role
        $this->client->addUserToGroup($keycloakUser->id, $role->getKey());
        $user->roles = [$role];
    }
}
