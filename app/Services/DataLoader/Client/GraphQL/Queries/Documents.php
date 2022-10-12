<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Queries;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class Documents extends GraphQL {
    public function getSelector(): string {
        return 'getDocuments';
    }

    public function __toString(): string {
        return /** @lang GraphQL */ <<<GRAPHQL
        query getDocuments(\$limit: Int, \$lastId: String, \$from: String) {
            getDocuments(limit: \$limit, lastId: \$lastId, fromTimestamp: \$from) {
                {$this->getDocumentPropertiesGraphQL()}
            }
        }
        GRAPHQL;
    }
}
