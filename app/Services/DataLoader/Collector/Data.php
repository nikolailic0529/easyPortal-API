<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Collector;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Data\Location;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\Reseller;
use App\Services\DataLoader\Schema\Types\CompanyKpis as SchemaCompanyKpis;
use App\Services\DataLoader\Schema\Types\Document as SchemaDocument;
use App\Services\DataLoader\Schema\Types\DocumentEntry as SchemaDocumentEntry;
use App\Services\DataLoader\Schema\Types\ViewAsset as SchemaViewAsset;
use App\Services\DataLoader\Schema\Types\ViewAssetDocument as SchemaViewAssetDocument;
use App\Services\DataLoader\Schema\Types\ViewDocument as SchemaViewDocument;
use App\Utils\Eloquent\Model;
use Illuminate\Support\Arr;

use function is_array;

class Data {
    /**
     * We are using a whitelist here to reduce memory usage.
     *
     * @var array<class-string<Model>,array<string,string>>
     */
    private array $data = [
        Distributor::class => [],
        Reseller::class    => [],
        Customer::class    => [],
        Document::class    => [],
        Location::class    => [],
        Asset::class       => [],
    ];

    private bool $dirty = false;

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
     * @return array<class-string<Model>,array<string,string>>
     */
    public function getData(): array {
        return $this->data;
    }

    public function collect(mixed $object): static {
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
        } elseif ($object instanceof Document) {
            $this->add(Document::class, $object->getKey());
            $this->add(Reseller::class, $object->reseller_id);
            $this->add(Customer::class, $object->customer_id);
            $this->add(Distributor::class, $object->distributor_id);
        } elseif ($object instanceof Model) {
            $this->add($object::class, $object->getKey());
        } else {
            // empty
        }

        return $this;
    }

    /**
     * @param class-string<Model> $class
     */
    public function add(string $class, ?string $key): static {
        if ($key && isset($this->data[$class])) {
            $this->data[$class][$key] = $key;
        }

        return $this;
    }

    /**
     * @param class-string<Model> $class
     * @param array<string>       $keys
     */
    public function addAll(string $class, array $keys): static {
        foreach ($keys as $key) {
            $this->add($class, $key);
        }

        return $this;
    }

    public function addData(Data $data): static {
        foreach ($data->getData() as $class => $keys) {
            if (isset($this->data[$class])) {
                $this->data[$class] += $keys;
            }
        }

        return $this;
    }

    /**
     * @param class-string<Model> $class
     * @param array<string>       $keys
     */
    public function deleteAll(string $class, array $keys = null): static {
        if (is_array($keys)) {
            foreach ($keys as $key) {
                unset($this->data[$class][$key]);
            }
        } else {
            $this->data[$class] = [];
        }

        return $this;
    }

    public function collectObjectChange(object $object): static {
        if (!$this->dirty) {
            $this->dirty = $object instanceof Model;
        }

        return $this->collect($object);
    }

    public function collectObjectDeletion(object $object): static {
        if (!$this->dirty) {
            $this->dirty = $object instanceof Model;
        }

        return $this->collect($object);
    }

    public function isEmpty(): bool {
        return Arr::first($this->data, static fn(array $data) => !!$data) === null;
    }

    public function isDirty(): bool {
        return $this->dirty;
    }
}
