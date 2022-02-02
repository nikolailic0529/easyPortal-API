<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Collector;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\Location;
use App\Models\Reseller;
use App\Services\DataLoader\Schema\CompanyKpis as SchemaCompanyKpis;
use App\Services\DataLoader\Schema\Document as SchemaDocument;
use App\Services\DataLoader\Schema\DocumentEntry as SchemaDocumentEntry;
use App\Services\DataLoader\Schema\ViewAsset as SchemaViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument as SchemaViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument as SchemaViewDocument;

class Data {
    /**
     * @var array<class-string,array<string,string>>
     */
    private array $data = [
        // empty
    ];

    public function __construct() {
        // empty
    }

    /**
     * @return array<string,string>
     */
    public function get(string $class): array {
        return $this->data[$class] ?? [];
    }

    public function collect(mixed $object): void {
        if ($object instanceof SchemaViewAsset) {
            $this->add(Reseller::class, $object->resellerId ?? null);
            $this->add(Reseller::class, $object->reseller->id ?? null);
            $this->add(Customer::class, $object->customerId ?? null);
            $this->add(Customer::class, $object->customer->id ?? null);
            $this->add(Asset::class, $object->id ?? null);
        } elseif ($object instanceof SchemaViewAssetDocument) {
            $this->add(Reseller::class, $object->reseller->id ?? null);
            $this->add(Customer::class, $object->customer->id ?? null);
        } elseif ($object instanceof SchemaViewDocument) {
            $this->add(Distributor::class, $object->distributorId ?? null);
            $this->add(Reseller::class, $object->resellerId ?? null);
            $this->add(Customer::class, $object->customerId ?? null);
            $this->add(Document::class, $object->id ?? null);
        } elseif ($object instanceof SchemaDocument) {
            $this->add(Distributor::class, $object->distributorId ?? null);
            $this->add(Reseller::class, $object->resellerId ?? null);
            $this->add(Customer::class, $object->customerId ?? null);
            $this->add(Document::class, $object->id ?? null);
        } elseif ($object instanceof SchemaDocumentEntry) {
            $this->add(Asset::class, $object->assetId ?? null);
        } elseif ($object instanceof SchemaCompanyKpis) {
            $this->add(Reseller::class, $object->resellerId ?? null);
        } elseif ($object instanceof Distributor) {
            $this->add(Distributor::class, $object->getKey());
        } elseif ($object instanceof Reseller) {
            $this->add(Reseller::class, $object->getKey());
        } elseif ($object instanceof Customer) {
            $this->add(Customer::class, $object->getKey());
        } elseif ($object instanceof Document) {
            $this->add(Document::class, $object->getKey());
        } elseif ($object instanceof Asset) {
            $this->add(Asset::class, $object->getKey());
        } elseif ($object instanceof Location) {
            $this->add(Location::class, $object->getKey());
        } else {
            // empty
        }
    }

    protected function add(string $class, ?string $id): void {
        if ($id) {
            $this->data[$class][$id] = $id;
        }
    }
}
