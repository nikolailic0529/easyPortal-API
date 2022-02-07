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
use Illuminate\Database\Eloquent\Model;

class Data {
    /**
     * We are using a whitelist here to reduce memory usage.
     *
     * @var array<class-string,array<string,string>>
     */
    private array $data = [
        Distributor::class => [],
        Reseller::class    => [],
        Customer::class    => [],
        Document::class    => [],
        Location::class    => [],
        Asset::class       => [],
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

    /**
     * @return array<class-string,array<string,string>>
     */
    public function getData(): array {
        return $this->data;
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
        } elseif ($object instanceof Asset) {
            $this->add(Asset::class, $object->getKey());
            $this->add(Reseller::class, $object->reseller_id);
            $this->add(Customer::class, $object->customer_id);
            $this->add(Location::class, $object->location_id);
        } elseif ($object instanceof Model) {
            $this->add($object::class, $object->getKey());
        } else {
            // empty
        }
    }

    protected function add(string $class, ?string $id): void {
        if ($id && isset($this->data[$class])) {
            $this->data[$class][$id] = $id;
        }
    }
}
