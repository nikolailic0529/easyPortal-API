<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Location;
use App\Models\Reseller;
use App\Utils\Eloquent\Callbacks\GetKey;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
class ChunkData {
    /**
     * @var \Illuminate\Support\Collection<TModel>
     */
    protected Collection $items;

    /**
     * @var array<string>
     */
    protected array $keys;

    /**
     * @var array<string, array<string, int>>
     */
    protected array $assetsCount;

    /**
     * @var array<string, array<string, array<string, int>>>
     */
    protected array $assetsCountFor;

    /**
     * @param \Illuminate\Support\Collection<TModel>|array<TModel> $items
     */
    public function __construct(Collection|array $items) {
        $this->items = new Collection($items);
    }

    /**
     * @return array<string>
     */
    public function getKeys(): array {
        if (!isset($this->keys)) {
            $this->keys = $this->items->map(new GetKey())->all();
        }

        return $this->keys;
    }

    public function getResellerAssetsCount(Reseller $reseller): int {
        return $this->getAssetsCount('reseller_id')[$reseller->getKey()] ?? 0;
    }

    public function getResellerAssetsCountFor(Reseller $reseller, Location $location): int {
        return $this->getAssetsCountFor('reseller_id', 'location_id')[$reseller->getKey()][$location->getKey()] ?? 0;
    }

    public function getCustomerAssetsCount(Customer $customer): int {
        return $this->getAssetsCount('customer_id')[$customer->getKey()] ?? 0;
    }

    public function getCustomerAssetsCountFor(Customer $customer, CustomerLocation $location): int {
        return $this->getAssetsCountFor('customer_id', 'location_id')[$customer->getKey()][$location->location_id] ?? 0;
    }

    public function getLocationAssetsCount(Location $location): int {
        return $this->getAssetsCount('location_id')[$location->getKey()] ?? 0;
    }

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @return array<string,int>
     */
    protected function getAssetsCount(string $owner): array {
        if (!isset($this->assetsCount[$owner])) {
            // Calculate
            $data   = [];
            $keys   = $this->getKeys();
            $result = Asset::query()
                ->select([$owner, DB::raw('count(*) as count')])
                ->whereIn($owner, $keys)
                ->groupBy($owner)
                ->toBase()
                ->get();

            foreach ($result as $row) {
                /** @var \stdClass $row */
                $data[$row->{$owner}] = (int) $row->count;
            }

            // Save
            $this->assetsCount[$owner] = $data;
        }

        return $this->assetsCount[$owner];
    }

    /**
     * @return array<string, array<string, array<string, int>>>
     */
    protected function getAssetsCountFor(string $owner, string $group): array {
        if (!isset($this->assetsCountFor[$owner][$group])) {
            // Calculate
            $data   = [];
            $keys   = $this->getKeys();
            $result = Asset::query()
                ->select([$owner, $group, DB::raw('count(*) as count')])
                ->whereIn($owner, $keys)
                ->groupBy($owner, $group)
                ->toBase()
                ->get();

            foreach ($result as $row) {
                /** @var \stdClass $row */
                $o = (string) $row->{$owner};
                $g = (string) $row->{$group};

                $data[$o][$g] = (int) $row->count + ($data[$o][$g] ?? 0);
            }

            // Save
            $this->assetsCountFor[$owner][$group] = $data;
        }

        return $this->assetsCountFor[$owner][$group];
    }
    // </editor-fold>
}
