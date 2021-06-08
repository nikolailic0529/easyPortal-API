<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Events;

use App\Services\DataLoader\Exceptions\InvalidData;
use App\Services\DataLoader\Schema\Asset;

class InvalidDataFound {
    public function __construct(
        protected InvalidData $data,
        protected Asset|null $object = null,
    ) {
        // empty
    }

    public function getData(): InvalidData {
        return $this->data;
    }

    public function getObject(): Asset|null {
        return $this->object;
    }

    /**
     * @inheritDoc
     */
    public function getContext(): array {
        return $this->getData()->context();
    }
}
