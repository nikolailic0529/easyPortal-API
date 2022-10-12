<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Queries;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class CustomerDocumentsCount extends GraphQL {
    public function getSelector(): string {
        return 'getDocumentsByCustomerCount';
    }

    public function __toString(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
        query value($id: String!, $from: String) {
            getDocumentsByCustomerCount(customerId: $id, fromTimestamp: $from)
        }
        GRAPHQL;
    }
}
