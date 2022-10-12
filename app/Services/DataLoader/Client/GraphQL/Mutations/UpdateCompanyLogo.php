<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Mutations;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class UpdateCompanyLogo extends GraphQL {
    public function getSelector(): string {
        return 'updateCompanyLogo';
    }

    /**
     * @inheritDoc
     */
    public function __toString() {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            mutation updateCompanyLogo($input: UpdateCompanyFile!) {
                updateCompanyLogo(input: $input)
            }
        GRAPHQL;
    }
}
