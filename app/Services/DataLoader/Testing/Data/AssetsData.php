<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

abstract class AssetsData extends Data {
    /**
     * @return array<mixed>
     */
    protected function generateContext(string $path): array {
        return $this->app->make(ClientDumpContext::class)->get($path, [
            ClientDumpContext::DISTRIBUTORS,
            ClientDumpContext::RESELLERS,
            ClientDumpContext::CUSTOMERS,
            ClientDumpContext::TYPES,
            ClientDumpContext::OEMS,
        ]);
    }

    abstract protected function generateData(string $path): bool;
}
