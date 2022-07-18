<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\GraphQL\Mutations\Message\Create;
use App\GraphQL\Objects\MessageInput;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Support\Arr;

/**
 * @deprecated {@see \App\GraphQL\Mutations\Org\ChangeRequest\Create}
 */
class RequestOrgChange {
    public function __construct(
        protected CurrentOrganization $organization,
        protected Create $mutation,
    ) {
        // empty
    }

    /**
     * @param array{input: array<string, mixed>} $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        $org     = $this->organization->get();
        $input   = Arr::except($args['input'], ['from']);
        $message = new MessageInput($input);
        $request = $this->mutation->createRequest($org, $message);

        return [
            'created' => $request,
        ];
    }
}
