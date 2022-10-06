<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Mutations;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class UpdateCompanyFavicon extends GraphQL {
    public function getSelector(): string {
        return 'updateCompanyFavicon';
    }

    /**
     * @inheritDoc
     */
    public function __toString() {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            mutation updateCompanyFavicon($input: UpdateCompanyFile!) {
                updateCompanyFavicon(input: $input)
            }
        GRAPHQL;
    }
}
