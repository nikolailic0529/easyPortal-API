<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Queries;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class CustomerDocuments extends GraphQL {
    public function getSelector(): string {
        return 'getDocumentsByCustomer';
    }

    public function __toString(): string {
        return /** @lang GraphQL */ <<<GRAPHQL
        query getDocumentsByCustomer(\$id: String!, \$limit: Int, \$lastId: String, \$from: String) {
            getDocumentsByCustomer(customerId: \$id, limit: \$limit, lastId: \$lastId, fromTimestamp: \$from) {
                {$this->getDocumentPropertiesGraphQL()}
            }
        }
        GRAPHQL;
    }
}
