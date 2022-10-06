<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Queries;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class DocumentsCount extends GraphQL {
    public function getSelector(): string {
        return 'getCentralAssetDbStatistics.documentsAmount';
    }

    public function __toString(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
        query {
            getCentralAssetDbStatistics {
                documentsAmount
            }
        }
        GRAPHQL;
    }
}
