<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer;

use App\Services\DataLoader\Collector\Data;
use App\Services\DataLoader\Schema\Type;
use App\Utils\JsonObject\JsonObjectIterator;

class ImporterChunkData extends Data {
    /**
     * @param array<Type> $items
     */
    public function __construct(array $items) {
        parent::__construct();

        foreach ((new JsonObjectIterator($items)) as $item) {
            $this->collect($item);
        }
    }
}
