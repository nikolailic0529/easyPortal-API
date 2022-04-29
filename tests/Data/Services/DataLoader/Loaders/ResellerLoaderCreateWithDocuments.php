<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Services\DataLoader\Testing\Data\ClientDumpContext;

class ResellerLoaderCreateWithDocuments extends ResellerLoaderCreateWithoutAssets {
    public const RESELLER  = '0309df71-936c-44ce-9477-b5e9221fc96a';
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
