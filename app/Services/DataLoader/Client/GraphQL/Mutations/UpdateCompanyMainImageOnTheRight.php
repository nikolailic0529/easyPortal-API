<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Mutations;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class UpdateCompanyMainImageOnTheRight extends GraphQL {
    public function getSelector(): string {
        return 'updateCompanyMainImageOnTheRight';
    }

    /**
     * @inheritDoc
     */
    public function __toString() {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            mutation updateCompanyMainImageOnTheRight($input: UpdateCompanyFile!) {
                updateCompanyMainImageOnTheRight(input: $input)
            }
        GRAPHQL;
    }
}
