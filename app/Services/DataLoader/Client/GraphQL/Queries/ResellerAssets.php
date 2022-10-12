<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Queries;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class ResellerAssets extends GraphQL {
    public function getSelector(): string {
        return 'getAssetsByResellerId';
    }

    public function __toString(): string {
        return /** @lang GraphQL */ <<<GRAPHQL
        query items(\$id: String!, \$limit: Int, \$lastId: String, \$from: String) {
            getAssetsByResellerId(resellerId: \$id, limit: \$limit, lastId: \$lastId, fromTimestamp: \$from) {
                {$this->getAssetPropertiesGraphQL()}
                {$this->getAssetDocumentsPropertiesGraphQL()}
            }
        }
        GRAPHQL;
    }
}
