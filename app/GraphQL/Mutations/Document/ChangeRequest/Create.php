<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Document\ChangeRequest;

use App\GraphQL\Mutations\Message\Create as Mutation;
use App\GraphQL\Objects\MessageInput;
use App\Models\ChangeRequest;
use App\Models\Document;

class Create {
    public function __construct(
        protected Mutation $mutation,
    ) {
        // empty
    }

    /**
     * @param array{input: array<string, mixed>} $args
     */
    public function __invoke(Document $root, array $args): ChangeRequest {
        $input   = new MessageInput($args['input']);
        $request = $this->mutation->createRequest($root, $input);

        return $request;
    }
}
