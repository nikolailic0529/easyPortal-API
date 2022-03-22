<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Document;

class RequestQuoteChange {
    public function __construct(
        protected RequestAssetChange $requestAssetChange,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        $quote   = Document::whereKey($args['input']['quote_id'])->first();
        $request = $this->requestAssetChange->createRequest(
            $quote,
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
