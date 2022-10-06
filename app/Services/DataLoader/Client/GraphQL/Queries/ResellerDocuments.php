<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Queries;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class ResellerDocuments extends GraphQL {
    public function getSelector(): string {
        return 'getDocumentsByReseller';
    }

    public function __toString(): string {
        return /** @lang GraphQL */ <<<GRAPHQL
        query getDocumentsByReseller(\$id: String!, \$limit: Int, \$lastId: String, \$from: String) {
            getDocumentsByReseller(resellerId: \$id, limit: \$limit, lastId: \$lastId, fromTimestamp: \$from) {
                {$this->getDocumentPropertiesGraphQL()}
            }
        }
        GRAPHQL;
    }
}
