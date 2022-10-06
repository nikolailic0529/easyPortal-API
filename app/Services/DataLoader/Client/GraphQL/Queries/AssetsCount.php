<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Queries;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class AssetsCount extends GraphQL {
    public function getSelector(): string {
        return 'getCentralAssetDbStatistics.assetsAmount';
    }

    public function __toString(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
        query {
            getCentralAssetDbStatistics {
                assetsAmount
            }
        }
        GRAPHQL;
    }
}
