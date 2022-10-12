<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Queries;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class CustomersCountFrom extends GraphQL {
    public function getSelector(): string {
        return 'getCustomerCount';
    }

    public function __toString(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
        query value($from: String) {
            getCustomerCount(fromTimestamp: $from)
        }
        GRAPHQL;
    }
}
