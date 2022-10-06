<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Mutations;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class UpdateBrandingData extends GraphQL {
    public function getSelector(): string {
        return 'updateBrandingData';
    }

    /**
     * @inheritDoc
     */
    public function __toString() {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            mutation updateBrandingData($input: CompanyBrandingData!) {
                updateBrandingData(input: $input)
            }
        GRAPHQL;
    }
}
