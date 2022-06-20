<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer;

use App\Services\DataLoader\Collector\Data;

use function array_diff_key;
use function array_keys;
use function array_merge;
use function array_unique;
use function count;

/**
 * We don't exactly know which models should be updated after the chunk, so we
 * are collecting all affected/related objects that (potentially) should be
 * updated. The problem is that the chunk may contain only a few objects with
 * some type, the next chunk also may contain the few objects with the same
 * type (or the same objects), etc. It leads to each of that objects will be
 * dispatched in each chunk and e.g. we will have a too many of "update"
 * tasks in Redis.
 *
 * To reduce the number of the events, we group objects from different chunks
 * and dispatch event only if the count of the objects is greater than the
 * threshold and they are not used in the current chunk.
 */
class ImporterCollectedData {
    private Data $data;

    public function __construct() {
        $this->data = new Data();
    }

    public function getData(): ?Data {
        return !$this->data->isEmpty()
            ? $this->data
            : null;
    }

    public function collect(int $threshold, Data $data): ?Data {
        // No changes or empty?
        if (!$data->isDirty() || $data->isEmpty()) {
            return null;
        }

        // No data?
        if ($this->data->isEmpty()) {
            $this->data->addData($data);

            return null;
        }

        // Collect unused
        $collectedData = $this->data->getData();
        $currentData   = $data->getData();
        $unusedData    = new Data();
        $keys          = array_unique(array_merge(array_keys($collectedData), array_keys($currentData)));

        foreach ($keys as $key) {
            $current = $currentData[$key] ?? [];
            $unused  = array_diff_key($collectedData[$key] ?? [], $current);

            if (count($unused) >= $threshold) {
                $this->data->deleteAll($key, $unused);
                $this->data->addAll($key, array_diff_key($current, $unused));
                $unusedData->addAll($key, $unused);
            } else {
                $this->data->addAll($key, $current);
            }
        }

        // Return
        return !$unusedData->isEmpty()
            ? $unusedData
            : null;
    }
}
