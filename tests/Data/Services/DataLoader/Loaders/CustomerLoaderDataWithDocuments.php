<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Services\DataLoader\Testing\Data\Context;

class CustomerLoaderDataWithDocuments extends CustomerLoaderData {
    public const CUSTOMER  = '004d6d19-4a7d-4216-8bd5-55dbfb038e09';
    public const DOCUMENTS = true;
    public const DOCUMENT  = '070157ef-c7c4-4a57-8e67-94c30dfe068e';

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
