<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org\ChangeRequest;

use App\GraphQL\Mutations\Message\Create as Mutation;
use App\GraphQL\Objects\MessageInput;
use App\Models\ChangeRequest;
use App\Models\Organization;
use App\Services\Auth\Auth;

class Create {
    public function __construct(
        protected Auth $auth,
        protected Mutation $mutation,
    ) {
        // empty
    }

    /**
     * @param array{input: array<string, mixed>} $args
     */
    public function __invoke(Organization $root, array $args): ChangeRequest {
        $input   = new MessageInput($args['input']);
        $request = $this->mutation->createRequest($root, $input);

        return $request;
    }
}
