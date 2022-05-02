<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Services\DataLoader\Testing\Data\ClientDumpContext;

class CustomerLoaderCreateWithDocuments extends CustomerLoaderCreateWithoutAssets {
    public const CUSTOMER  = 'a04716f3-95de-4046-ab13-7a575cf67f85';
    public const DOCUMENTS = true;
    public const DOCUMENT  = '00194785-9fba-4865-8106-7a833082c1cc';

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
