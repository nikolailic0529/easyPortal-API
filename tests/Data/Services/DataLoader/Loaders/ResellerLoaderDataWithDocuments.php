<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Services\DataLoader\Testing\Data\ClientDumpContext;

class ResellerLoaderDataWithDocuments extends ResellerLoaderData {
    public const RESELLER  = '22d7dbf2-977a-49d8-8e4c-cacdaae616b2';
    public const DOCUMENTS = true;
    public const DOCUMENT  = '171bce84-3caa-4471-942c-ef8539de5eb0';

    /**
     * @inheritDoc
     */
    protected function generateContext(string $path): array {
        return $this->app->make(ClientDumpContext::class)->get($path, [
            ClientDumpContext::DISTRIBUTORS,
            ClientDumpContext::RESELLERS,
            ClientDumpContext::CUSTOMERS,
            ClientDumpContext::ASSETS,
            ClientDumpContext::TYPES,
            ClientDumpContext::OEMS,
        ]);
    }
}
