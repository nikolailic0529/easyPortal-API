<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Mail\InviteOrganizationUser;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\Team;
use App\Models\User as UserModel;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Exceptions\RealmUserAlreadyExists;
use App\Services\KeyCloak\Client\Types\User;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Facades\Date;

use function strtr;

/**
 * @deprecated
 */
class InviteOrgUser {
    public function __construct(
        protected Client $client,
        protected Mailer $mailer,
        protected CurrentOrganization $organization,
        protected Encrypter $encrypter,
        protected Repository $config,
        protected UrlGenerator $generator,
        protected AuthManager $auth,
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
        $role         = $organization->roles()->whereKey($args['input']['role_id'])->first();
        $email        = $args['input']['email'];
        $invited      = false;
        $team         = null;

        if (isset($args['input']['team_id'])) {
            $team = Team::query()->whereKey($args['input']['team_id'])->first();
        }

        try {
            $this->client->createUser($email, $role);
            $invited = true;
        } catch (RealmUserAlreadyExists $e) {
            $invited = false;
        }

        $keycloakUser = $this->client->getUserByEmail($email, $organization);
        if (!$invited) {
            $this->client->addUserToGroup($keycloakUser->id, $role->getKey());
        }
        // Get User
        $user = $this->getUser($keycloakUser, $organization, $role, $team);

        // Create Invitation
        $invitation = $this->createInvitation($organization, $user, $role);

        // Send invitation email
        $url = null;
        // verified ? sign In : sign up
        if ($keycloakUser->emailVerified) {
            // Go to login page
            $url = $this->generator->to(strtr($this->config->get('ep.client.signin_invite_uri'), [
                '{organization}' => $organization->getKey(),
            ]));
        } else {
            // Go to invitation sign up page
            $token = $this->encrypter->encrypt([
                'invitation' => $invitation->getKey(),
            ]);
            $url   = $this->generator->to(strtr($this->config->get('ep.client.signup_invite_uri'), [
                '{token}' => $token,
            ]));
        }
        $this->mailer->to($email)->send(new InviteOrganizationUser($url));
        return ['result' => true ];
    }

    protected function getUser(User $keycloakUser, Organization $organization, Role $role, ?Team $team): UserModel {
        $user = UserModel::query()->whereKey($keycloakUser->id)->first();
        if (!$user) {
            // create a new user
            $user                        = new UserModel();
            $user->{$user->getKeyName()} = $keycloakUser->id;
            $user->email                 = $keycloakUser->email;
            $user->email_verified        = false;
            $user->permissions           = [];
        }

        // Save
        $user->save();

        // Add to organization
        $organizationUser = OrganizationUser::query()
            ->where('organization_id', '=', $organization->getKey())
            ->where('user_id', '=', $user->getKey())
            ->first();
        if (!$organizationUser) {
            $organizationUser                  = new OrganizationUser();
            $organizationUser->organization_id = $organization->getKey();
            $organizationUser->user_id         = $user->getKey();
        }
        $organizationUser->team_id = $team?->getKey();
        $organizationUser->role_id = $role->getKey();
        $organizationUser->enabled = true;
        $organizationUser->save();

        // Save
        $user->save();

        return $user;
    }

    protected function createInvitation(Organization $organization, UserModel $user, Role $role): Invitation {
        $invitation                  = new Invitation();
        $invitation->organization_id = $organization->getKey();
        $invitation->sender_id       = $this->auth->user()->getAuthIdentifier();
        $invitation->user_id         = $user->getKey();
        $invitation->role_id         = $role->getKey();
        $invitation->email           = $user->email;
        $invitation->used_at         = null;
        $invitation->expired_at      = Date::now()->add($this->config->get('ep.invite_expire'));
        $invitation->save();
        return $invitation;
    }
}
