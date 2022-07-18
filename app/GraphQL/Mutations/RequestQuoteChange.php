<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\GraphQL\Mutations\Message\Create;
use App\GraphQL\Objects\MessageInput;
use App\Models\Document;
use Illuminate\Support\Arr;

/**
 * @deprecated {@see \App\GraphQL\Mutations\Document\ChangeRequest\Create}
 */
class RequestQuoteChange {
    public function __construct(
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
        $quote   = Document::query()->whereKey($args['input']['quote_id'])->firstOrFail();
        $input   = Arr::except($args['input'], ['from', 'quote_id']);
        $message = new MessageInput($input);
        $request = $this->mutation->createRequest($quote, $message);

        return [
            'created' => $request,
        ];
    }
}
