<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor\Processors;

use App\Models\Customer;
use App\Services\Recalculator\Processor\Processor;
use App\Utils\Processor\EloquentState;
use App\Utils\Processor\State;
use Illuminate\Database\Eloquent\Collection;

use function count;

/**
 * @extends Processor<Customer,CustomersChunkData>
 */
class CustomersProcessor extends Processor {
    protected function getModel(): string {
        return Customer::class;
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        $items = (new Collection($items))->load(['locations', 'contacts', 'statuses']);
        $data  = new CustomersChunkData($items);

        return $data;
    }

    /**
     * @param EloquentState<Customer> $state
     * @param CustomersChunkData      $data
     * @param Customer                $item
     */
    protected function process(State $state, mixed $data, mixed $item): void {
        $item->locations_count = count($item->locations);
        $item->contacts_count  = count($item->contacts);
        $item->statuses_count  = count($item->statuses);
        $item->assets_count    = $data->getCustomerAssetsCount($item);

        foreach ($item->locations as $location) {
            $location->assets_count = $data->getCustomerAssetsCountFor($item, $location);
            $location->save();
        }

        $item->save();
    }
}
