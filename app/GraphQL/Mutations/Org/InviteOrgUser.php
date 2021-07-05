<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\GraphQL\Mutations\Auth\SignUpByInvite;
use App\Mail\InviteOrganizationUser;
use App\Mail\InviteToSignIn;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Exceptions\UserAlreadyExists;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Mail\Mailer;

use function array_key_exists;
use function json_decode;

class InviteOrgUser {
    public function __construct(
        protected Client $client,
        protected Mailer $mailer,
        protected CurrentOrganization $organization,
        protected Encrypter $encrypter,
        protected SignUpByInvite $signUpByInvite,
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
                    $output = $this->signUpByInvite->getSignInUri($organization->getKey());
                    $this->mailer->to($email)->send(new InviteToSignIn($output['url']));
                    return ['result' => true ];
                }
            }
        }
        $token = $this->encrypter->encrypt([
            'email'        => $email,
            'organization' => $organization->getKey(),
        ]);
        $this->mailer->to($email)->send(new InviteOrganizationUser($token));
        return ['result' => true ];
    }
}
