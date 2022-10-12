<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Queries;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class Assets extends GraphQL {
    public function getSelector(): string {
        return 'getAssets';
    }

    public function __toString(): string {
        return /** @lang GraphQL */ <<<GRAPHQL
        query items(\$limit: Int, \$lastId: String, \$from: String) {
            getAssets(limit: \$limit, lastId: \$lastId, fromTimestamp: \$from) {
                {$this->getAssetPropertiesGraphQL()}
                {$this->getAssetDocumentsPropertiesGraphQL()}
            }
        }
        GRAPHQL;
    }
}
