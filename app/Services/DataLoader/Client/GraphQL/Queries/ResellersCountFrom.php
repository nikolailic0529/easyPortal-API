<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Queries;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class ResellersCountFrom extends GraphQL {
    public function getSelector(): string {
        return 'getResellerCount';
    }

    public function __toString(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
        query value($from: String) {
            getResellerCount(fromTimestamp: $from)
        }
        GRAPHQL;
    }
}
