<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Mail\InviteOrganizationUser;
use App\Models\Invitation;
use App\Models\User as UserModel;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Exceptions\UserAlreadyExists;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Routing\UrlGenerator;

use function array_key_exists;
use function strtr;

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
        if (!$role) {
            throw new InviteOrgUserInvalidRole();
        }
        $email = $args['input']['email'];

        // Get User
        $user = UserModel::query()->where('email', '=', $email)->first();
        if ($user) {
            // in organization
            $inOrganization = $organization
                ->users()
                ->where($user->getQualifiedKeyName(), '=', $user->getKey())
                ->exists();
            if ($inOrganization) {
                throw new InviteOrgUserAlreadyInOrganization();
            }
        }

        try {
            $this->client->inviteUser($role, $email);
        } catch (UserAlreadyExists $e) {
            $keycloakUser = $this->client->getUserByEmail($email);
            $this->client->addUserToGroup($keycloakUser->id, $role->getKey());
            if (!empty($keycloakUser->credentials)) {
                // already has password
                $url = $this->generator->to(strtr($this->config->get('ep.client.signin_invite_uri'), [
                    '{organization}' => $organization->getKey(),
                ]));
                $this->mailer->to($email)->send(new InviteOrganizationUser($url));
                return ['result' => true ];
            }
        }
        // Create Invitation
        $invitation                  = new Invitation();
        $invitation->organization_id = $organization->getKey();
        $invitation->user_id         = $this->auth->user()->getAuthIdentifier();
        $invitation->role_id         = $role->getKey();
        $invitation->email           = $email;
        $invitation->team            = array_key_exists('team', $args['input']) ? $args['input']['team'] : null;
        $invitation->used            = false;
        $invitation->used_at         = null;
        $invitation->save();
        $token = $this->encrypter->encrypt([
            'invitation' => $invitation->getKey(),
        ]);
        $url   = $this->generator->to(strtr($this->config->get('ep.client.signup_invite_uri'), [
            '{token}' => $token,
        ]));
        $this->mailer->to($email)->send(new InviteOrganizationUser($url));
        return ['result' => true ];
    }
}
