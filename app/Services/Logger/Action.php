<?php declare(strict_types = 1);

namespace App\Services\Logger;

use App\Services\Logger\Models\Log;

use function microtime;

class Action {
    protected float $start;

    public function __construct(
        protected Log $log,
    ) {
        $this->start = microtime(true);
    }

    public function getKey(): string {
        return $this->getLog()->getKey();
    }

    public function getLog(): Log {
        return $this->log;
    }


    public function getDuration(): float {
        return microtime(true) - $this->start;
    }
}
