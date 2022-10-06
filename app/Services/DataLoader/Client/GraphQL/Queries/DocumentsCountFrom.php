<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Queries;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class DocumentsCountFrom extends GraphQL {
    public function getSelector(): string {
        return 'getDocumentCount';
    }

    public function __toString(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
        query value($from: String) {
            getDocumentCount(fromTimestamp: $from)
        }
        GRAPHQL;
    }
}
