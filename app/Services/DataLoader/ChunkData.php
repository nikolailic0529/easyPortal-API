<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Services\DataLoader\Schema\CompanyKpis;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\DocumentEntry;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use App\Utils\JsonObject\JsonObjectIterator;

class ChunkData {
    /**
     * @var array<string,string>
     */
    private array $distributors = [];
    /**
     * @var array<string,string>
     */
    private array $resellers = [];
    /**
     * @var array<string,string>
     */
    private array $customers = [];
    /**
     * @var array<string,string>
     */
    private array $documents = [];
    /**
     * @var array<string,string>
     */
    private array $assets = [];

    /**
     * @param array<\App\Services\DataLoader\Schema\Type> $items
     */
    public function __construct(array $items) {
        $this->extract($items);
    }

    /**
     * @return array<string,string>
     */
    public function getDistributors(): array {
        return $this->distributors;
    }

    /**
     * @return array<string,string>
     */
    public function getResellers(): array {
        return $this->resellers;
    }

    /**
     * @return array<string,string>
     */
    public function getCustomers(): array {
        return $this->customers;
    }

    /**
     * @return array<string,string>
     */
    public function getDocuments(): array {
        return $this->documents;
    }

    /**
     * @return array<string,string>
     */
    public function getAssets(): array {
        return $this->assets;
    }

    /**
     * @param array<\App\Services\DataLoader\Schema\Type> $items
     */
    protected function extract(array $items): void {
        foreach ((new JsonObjectIterator($items)) as $item) {
            $this->process($item);
        }
    }

    protected function process(Type $item): void {
        if ($item instanceof ViewAsset) {
            $this->addReseller($item->resellerId ?? null);
            $this->addReseller($item->reseller->id ?? null);
            $this->addCustomer($item->customerId ?? null);
            $this->addCustomer($item->customer->id ?? null);
            $this->addAsset($item->id ?? null);
        } elseif ($item instanceof ViewAssetDocument) {
            $this->addReseller($item->reseller->id ?? null);
            $this->addCustomer($item->customer->id ?? null);
        } elseif ($item instanceof ViewDocument) {
            $this->addDistributor($item->distributorId ?? null);
            $this->addReseller($item->resellerId ?? null);
            $this->addCustomer($item->customerId ?? null);
            $this->addDocument($item->id ?? null);
        } elseif ($item instanceof Document) {
            $this->addDistributor($item->distributorId ?? null);
            $this->addReseller($item->resellerId ?? null);
            $this->addCustomer($item->customerId ?? null);
            $this->addDocument($item->id ?? null);
        } elseif ($item instanceof DocumentEntry) {
            $this->addAsset($item->assetId ?? null);
        } elseif ($item instanceof CompanyKpis) {
            $this->addReseller($item->resellerId ?? null);
        } else {
            // empty
        }
    }

    protected function addDistributor(?string $id): void {
        if ($id) {
            $this->distributors[$id] = $id;
        }
    }

    protected function addReseller(?string $id): void {
        if ($id) {
            $this->resellers[$id] = $id;
        }
    }

    protected function addCustomer(?string $id): void {
        if ($id) {
            $this->customers[$id] = $id;
        }
    }

    protected function addDocument(?string $id): void {
        if ($id) {
            $this->documents[$id] = $id;
        }
    }

    protected function addAsset(?string $id): void {
        if ($id) {
            $this->assets[$id] = $id;
        }
    }
}
