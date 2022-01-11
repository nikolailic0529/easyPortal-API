<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\Auth;
use App\Services\Filesystem\ModelDiskFactory;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User as KeycloakUser;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

/**
 * @deprecated
 */
class UpdateOrgUser {
    public function __construct(
        protected Auth $auth,
        protected Client $client,
        protected CurrentOrganization $organization,
        protected ModelDiskFactory $disks,
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

        return ['result' => $this->updateUser($user, Arr::except($input, ['user_id']), $organizationUser)];
    }

        /**
     * @param array<string, mixed> $input
     */
    public function updateUser(
        User $user,
        array $input,
        OrganizationUser $organizationUser = null,
    ): bool {
        $userType     = new KeycloakUser();
        $keycloakUser = $this->client->getUserById($user->getKey());
        $attributes   = $keycloakUser->attributes;
        foreach ($input as $property => $value) {
            switch ($property) {
                case 'given_name':
                    $userType->firstName = $value;
                    $user->given_name    = $value;
                    break;
                case 'family_name':
                    $userType->lastName = $value;
                    $user->family_name  = $value;
                    break;
                case 'homepage':
                    $user->homepage = $value;
                    break;
                case 'locale':
                    $user->locale = $value;
                    break;
                case 'timezone':
                    $user->timezone = $value;
                    break;
                case 'photo':
                    $photo               = $this->store($user, $value);
                    $user->photo         = $photo;
                    $attributes['photo'] = [$photo];
                    break;
                case 'role_id':
                    if ($organizationUser && !$this->auth->isRoot($user)) {
                        $role = Role::query()->whereKey($input['role_id'])->first();
                        $this->role($organizationUser, $keycloakUser, $role);
                    }
                    break;
                case 'team_id':
                    if ($organizationUser) {
                        $organizationUser->team_id = $input['team_id'];
                    }
                    break;
                default:
                    $user->{$property}     = $value;
                    $attributes[$property] = [$value];
                    break;
            }
        }

        if ($organizationUser) {
            $organizationUser->save();
        }
        $userType->attributes = $attributes;

        return $this->client->updateUser($user->getKey(), $userType) && $user->save();
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

    protected function store(User $user, ?UploadedFile $file): ?string {
        $url = null;

        if ($file) {
            $disk = $this->disks->getDisk($user);
            $url  = $disk->url($disk->store($file));
        }

        return $url;
    }
}
