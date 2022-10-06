<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Queries;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class ResellerDocumentsCount extends GraphQL {
    public function getSelector(): string {
        return 'getDocumentsByResellerCount';
    }

    public function __toString(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
        query value($id: String!, $from: String) {
            getDocumentsByResellerCount(resellerId: $id, fromTimestamp: $from)
        }
        GRAPHQL;
    }
}
