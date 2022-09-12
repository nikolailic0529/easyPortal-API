<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Organization\CurrentOrganization;
use Illuminate\Auth\AuthManager;

class CreateContractNote {
    public function __construct(
        protected AuthManager $auth,
        protected CurrentOrganization $organization,
        protected CreateQuoteNote $createQuoteNote,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        return [
            'created' => $this->createQuoteNote->createNote(
                $args['input']['contract_id'],
                $args['input']['note'],
                $args['input']['pinned'] ?? false,
                $args['input']['files'] ?? [],
            ),
        ];
    }
}
