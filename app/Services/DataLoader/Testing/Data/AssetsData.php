<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use App\Services\DataLoader\Finders\OemFinder;
use App\Services\DataLoader\Testing\Finders\OemFinder as OemFinderImpl;

abstract class AssetsData extends Data {
    /**
     * @inheritDoc
     */
    protected function generateBindings(): array {
        return [
            OemFinder::class => OemFinderImpl::class,
        ];
    }

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
