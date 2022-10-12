<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Queries;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class Distributors extends GraphQL {
    public function getSelector(): string {
        return 'getDistributors';
    }

    public function __toString(): string {
        return /** @lang GraphQL */ <<<GRAPHQL
        query items(\$limit: Int, \$lastId: String, \$from: String) {
            getDistributors(limit: \$limit, lastId: \$lastId, fromTimestamp: \$from) {
                {$this->getDistributorPropertiesGraphQL()}
            }
        }
        GRAPHQL;
    }
}
