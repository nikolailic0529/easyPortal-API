<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Mail\InviteOrganizationUser;
use App\Services\KeyCloak\Client\Client;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Mail\Mailer;

class InviteOrgUser {
    public function __construct(
        protected Client $client,
        protected Mailer $mailer,
        protected CurrentOrganization $organization,
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
        $this->client->inviteUser($organization, $args['input']['email']);
        $this->mailer->to($args['input']['email'])->send(new InviteOrganizationUser($organization));
        return ['result' => true ];
    }
}
