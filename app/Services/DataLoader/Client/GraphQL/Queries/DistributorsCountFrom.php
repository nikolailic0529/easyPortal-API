<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Queries;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class DistributorsCountFrom extends GraphQL {
    public function getSelector(): string {
        return 'getDistributorCount';
    }

    public function __toString(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
        query value($from: String) {
            getDistributorCount(fromTimestamp: $from)
        }
        GRAPHQL;
    }
}
