<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use function microtime;
use function round;

class DataLoaderRequest {
    protected float $start;

    public function __construct(
        protected DataLoaderObject $object,
        protected ?string $transaction = null,
    ) {
        $this->start = microtime(true);
    }

    public function getObject(): DataLoaderObject {
        return $this->object;
    }

    public function getTransaction(): ?string {
        return $this->transaction;
    }

    public function getDuration(): ?float {
        return round((microtime(true) - $this->start) * 1000);
    }
}
