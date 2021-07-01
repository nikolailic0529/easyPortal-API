<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use function microtime;

class DataLoaderRequest {
    protected float $start;

    public function __construct(
        protected DataLoaderRequestObject $object,
        protected ?string $transaction = null,
    ) {
        $this->start = microtime(true);
    }

    public function getObject(): DataLoaderRequestObject {
        return $this->object;
    }

    public function getTransaction(): ?string {
        return $this->transaction;
    }

    public function getDuration(): float {
        return microtime(true) - $this->start;
    }
}
