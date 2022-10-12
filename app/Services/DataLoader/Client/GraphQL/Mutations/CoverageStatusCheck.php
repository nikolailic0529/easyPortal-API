<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\GraphQL\Mutations;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

class CoverageStatusCheck extends GraphQL {
    public function getSelector(): string {
        return 'triggerCoverageStatusCheck';
    }

    /**
     * @inheritDoc
     */
    public function __toString() {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            mutation triggerCoverageStatusCheck($input: TriggerCoverageStatusCheck!) {
                triggerCoverageStatusCheck(input: $input)
            }
        GRAPHQL;
    }
}
