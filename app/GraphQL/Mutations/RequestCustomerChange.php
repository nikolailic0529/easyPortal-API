<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Customer;

class RequestCustomerChange {
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
        $request = $this->requestAssetChange->createRequest(
            $args['input']['customer_id'],
            (new Customer())->getMorphClass(),
            $args['input']['subject'],
            $args['input']['message'],
            $args['input']['from'],
            $args['input']['cc'] ?? null,
            $args['input']['bcc'] ?? null,
            new Customer(),
        );
        return ['created' => $request];
    }
}
