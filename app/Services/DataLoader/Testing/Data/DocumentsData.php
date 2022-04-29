<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

abstract class DocumentsData extends AssetsData {
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
