<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Organization\CurrentOrganization;

class RequestOrganizationChange {
    public function __construct(
        protected RequestAssetChange $requestAssetChange,
        protected CurrentOrganization $organization,
    ) {
        // empty
    }
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        $organization = $this->organization->get();
        $request      = $this->requestAssetChange->createRequest(
            $organization,
            $args['input']['subject'],
            $args['input']['message'],
            $args['input']['from'],
            $args['input']['files'] ?? [],
            $args['input']['cc'] ?? null,
            $args['input']['bcc'] ?? null,
        );
        return ['created' => $request];
    }
}
