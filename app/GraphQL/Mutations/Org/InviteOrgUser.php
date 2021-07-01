<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Mail\InviteOrganizationUser;
use App\Models\Organization;
use App\Services\KeyCloak\Client\Client;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Mail\Mailer;

class InviteOrgUser {
    public function __construct(
        protected Client $client,
        protected Mailer $mailer,
        protected CurrentOrganization $organization,
        protected Encrypter $encrypter,
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
        // $organization = $this->organization->get();
        $organization = Organization::find('a7c3efa9-2be7-4114-a897-7a273a2c0ac5');
        $role         = $organization->roles()->whereKey($args['input']['role_id'])->first();
        if (!$role) {
            throw new InviteOrgUserInvalidRole();
        }
        $email  = $args['input']['email'];
        $result = $this->client->inviteUser($role, $email);
        $token  = $this->encrypter->encrypt([
            'email'        => $email,
            'organization' => $organization->getKey(),
        ]);
        $this->mailer->to($email)->send(new InviteOrganizationUser($token));
        return ['result' => $result ];
    }
}
