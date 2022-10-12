<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Queries;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class DocumentById extends GraphQL {
    public function getSelector(): string {
        return 'getDocumentById';
    }

    public function __toString(): string {
        return /** @lang GraphQL */ <<<GRAPHQL
        query getDocumentById(\$id: String!) {
            getDocumentById(id: \$id) {
                {$this->getDocumentPropertiesGraphQL()}
            }
        }
        GRAPHQL;
    }
}
