<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Queries;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class ResellerAssetsCount extends GraphQL {
    public function getSelector(): string {
        return 'getAssetsByResellerIdCount';
    }

    public function __toString(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
        query value($id: String!, $from: String) {
            getAssetsByResellerIdCount(resellerId: $id, fromTimestamp: $from)
        }
        GRAPHQL;
    }
}
