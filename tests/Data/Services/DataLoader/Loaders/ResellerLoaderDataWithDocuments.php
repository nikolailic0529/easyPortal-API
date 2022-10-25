<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Services\DataLoader\Testing\Data\Context;

class ResellerLoaderDataWithDocuments extends ResellerLoaderData {
    public const RESELLER  = '22d7dbf2-977a-49d8-8e4c-cacdaae616b2';
    public const DOCUMENTS = true;
    public const DOCUMENT  = '171bce84-3caa-4471-942c-ef8539de5eb0';

    /**
     * @inheritdoc
     */
    protected function getSupporterContext(): array {
        return [
            Context::DISTRIBUTORS,
            Context::RESELLERS,
            Context::CUSTOMERS,
            Context::ASSETS,
            Context::TYPES,
            Context::OEMS,
        ];
    }
}
