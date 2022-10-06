<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Queries;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class ResellerById extends GraphQL {
    public function getSelector(): string {
        return 'getCompanyById';
    }

    public function __toString(): string {
        return /** @lang GraphQL */ <<<GRAPHQL
        query getCompanyById(\$id: String!) {
            getCompanyById(id: \$id) {
                {$this->getResellerPropertiesGraphQL()}
            }
        }
        GRAPHQL;
    }
}
