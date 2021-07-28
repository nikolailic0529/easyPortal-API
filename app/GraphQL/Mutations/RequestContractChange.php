<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Document;

class RequestContractChange {
    public function __construct(
        protected RequestAssetChange $requestAssetChange,
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
        $contract = Document::whereKey($args['input']['contract_id'])->first();
        $request  = $this->requestAssetChange->createRequest(
            $contract,
            $args['input']['subject'],
            $args['input']['message'],
            $args['input']['from'],
            $args['input']['attachments'] ?? [],
            $args['input']['cc'] ?? null,
            $args['input']['bcc'] ?? null,
        );
        return ['created' => $request];
    }
}
