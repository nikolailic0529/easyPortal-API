<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth\Organization;

use App\GraphQL\Queries\Me;
use App\Models\Organization;
use App\Services\Keycloak\Exceptions\Auth\StateMismatch;
use App\Services\Keycloak\Keycloak;

class Authorize {
    public function __construct(
        protected Keycloak $keycloak,
        protected Me $query,
    ) {
        // empty
    }

    /**
     * @param array<string,array{input: array<string,mixed>}> $args
     */
    public function __invoke(Organization $organization, array $args): mixed {
        $me = null;

        try {
            $input = new AuthorizeInput($args['input']);
            $me    = $this->keycloak->authorize($organization, $input->code, $input->state);
            $me    = $this->query->getMe($me);
        } catch (StateMismatch) {
            // This may happen if the User opened multiple tabs. To allow UI to
            // handle this case we are returning `false` instead of error.
        }

        return [
            'result' => $me !== null,
            'me'     => $me,
        ];
    }
}
