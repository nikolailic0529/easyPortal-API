<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Mail\InviteOrganizationUser;
use App\Models\Role;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Exceptions\UserAlreadyExists;
use App\Services\KeyCloak\Client\Types\User;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Routing\UrlGenerator;

use function array_filter;
use function array_key_exists;
use function json_decode;
use function rtrim;
use function strtr;

class InviteOrgUser {
    public function __construct(
        protected Client $client,
        protected Mailer $mailer,
        protected CurrentOrganization $organization,
        protected Encrypter $encrypter,
        protected Repository $config,
        protected UrlGenerator $generator,
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
        try {
            $this->client->inviteUser($role, $email);
        } catch (UserAlreadyExists $e) {
            $user = $this->client->getUserByEmail($email);
            if ($user->attributes && array_key_exists('ep_invite', $user->attributes)) {
                $invitation = json_decode($user->attributes['ep_invite'][0]);
                if ($invitation->id) {
                    // completed his invitation
                    if (!$this->userInOrganization($user, $role)) {
                        // add user to organization
                        $this->client->addUserToGroup($user->id, $role->getKey());
                        $url   = $this->generator->to(strtr($this->config->get('ep.client.signin_invite_uri'), [
                            '{organization}' => $organization->getKey(),
                        ]));
                        $this->mailer->to($email)->send(new InviteOrganizationUser($url));
                    }
                    return ['result' => true ];
                }
            }
        }
        $token = $this->encrypter->encrypt([
            'email'        => $email,
            'organization' => $organization->getKey(),
        ]);
        $url   = $this->generator->to(strtr($this->config->get('ep.client.signup_invite_uri'), [
            '{token}' => $token,
        ]));
        $this->mailer->to($email)->send(new InviteOrganizationUser($url));
        return ['result' => true ];
    }

    protected function userInOrganization(User $user, Role $role): bool {
        $groups   = $this->client->getUserGroups($user->id);
        $filtered = array_filter($groups, static function ($group) use ($role) {
            return $group->id === $role->getKey();
        });
        return !empty($filtered);
    }
}
