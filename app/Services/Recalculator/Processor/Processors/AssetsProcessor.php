<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor\Processors;

use App\Models\Asset;
use App\Services\Recalculator\Processor\Processor;
use App\Utils\Processor\EloquentState;
use App\Utils\Processor\State;
use Illuminate\Database\Eloquent\Collection;

use function count;

/**
 * @extends Processor<Asset,AssetsChunkData, EloquentState<Asset>>
 */
class AssetsProcessor extends Processor {
    protected function getModel(): string {
        return Asset::class;
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        $items = (new Collection($items))->loadMissing(['coverages', 'contacts', 'warranties.document']);
        $data  = new AssetsChunkData($items);

        return $data;
    }

    /**
     * @param EloquentState<Asset> $state
     * @param AssetsChunkData      $data
     * @param Asset                $item
     */
    protected function process(State $state, mixed $data, mixed $item): void {
        $item->coverages_count = count($item->coverages);
        $item->contacts_count  = count($item->contacts);
        $item->warranty        = Asset::getLastWarranty($item->warranties);

        $item->save();
    }
}
