<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Events;

use App\Services\DataLoader\Collector\Data;

class DataImported {
    public function __construct(
        private Data $data,
    ) {
        // empty
    }

    public function getData(): Data {
        return $this->data;
    }
}
