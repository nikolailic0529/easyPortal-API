<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use App\Services\DataLoader\Finders\OemFinder;
use App\Services\DataLoader\Finders\ServiceGroupFinder;
use App\Services\DataLoader\Finders\ServiceLevelFinder;
use App\Services\DataLoader\Testing\Finders\OemFinder as OemFinderImpl;
use App\Services\DataLoader\Testing\Finders\ServiceGroupFinder as ServiceGroupFinderImpl;
use App\Services\DataLoader\Testing\Finders\ServiceLevelFinder as ServiceLevelFinderImpl;

abstract class AssetsData extends Data {
    /**
     * @inheritDoc
     */
    protected function generateBindings(): array {
        return [
            OemFinder::class          => OemFinderImpl::class,
            ServiceGroupFinder::class => ServiceGroupFinderImpl::class,
            ServiceLevelFinder::class => ServiceLevelFinderImpl::class,
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
