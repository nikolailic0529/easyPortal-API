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
use function json_decode;
use function json_encode;
use function str_contains;
use function str_replace;
use function strtr;
use function time;

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
            if ($user->attributes) {
                $usedInvitation       = false;
                $hasCurrentInvitation = false;
                foreach ($user->attributes as $key => $value) {
                    if (str_contains($key, 'ep_invite_')) {
                        // Get organization from invitation name
                        $invitationOrganization = str_replace('ep_invite_', '', $key);
                        if ($invitationOrganization === $organization->getKey()) {
                            $hasCurrentInvitation = true;
                        }
                        // invitation information
                        $data = json_decode($value[0]);
                        if ($invitationOrganization === $organization->getKey() && $data->used_at) {
                            // Already joined organization
                            throw new InviteOrgUserAlreadyUsedInvitation();
                        } elseif ($data->used_at) {
                            $usedInvitation = true;
                        } else {
                            // empty
                        }
                    }
                }
                if (!$hasCurrentInvitation) {
                    $attributes = $user->attributes;
                    // add new invitation
                    $attributes["ep_invite_{$organization->getKey()}"] = [
                        json_encode([
                            'sent_at' => time(),
                            'used_at' => null,
                        ]),
                    ];
                    $newData                                           = new User([
                        'attributes' => $attributes,
                    ]);
                    $this->client->updateUser($user->id, $newData);
                    $this->client->addUserToGroup($user->id, $role->getKey());
                }
                // Used an invitation before from any organization
                if ($usedInvitation) {
                    // add user to organization
                    $url = $this->generator->to(strtr($this->config->get('ep.client.signin_invite_uri'), [
                        '{organization}' => $organization->getKey(),
                    ]));
                    $this->mailer->to($email)->send(new InviteOrganizationUser($url));
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
