<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Queries;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class AssetById extends GraphQL {
    public function getSelector(): string {
        return 'getAssets';
    }

    public function __toString(): string {
        return /** @lang GraphQL */ <<<GRAPHQL
        query getAssets(\$id: String!) {
            getAssets(args: [{key: "id", value: \$id}]) {
                {$this->getAssetPropertiesGraphQL()}
                {$this->getAssetDocumentsPropertiesGraphQL()}
            }
        }
        GRAPHQL;
    }
}
