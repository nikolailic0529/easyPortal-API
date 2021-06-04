<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;

use function json_encode;

class QueueJob extends Job implements JobContract {
    /**
     * @inheritDoc
     *
     * @param array<mixed> $payload
     */
    public function __construct(
        protected $connectionName,
        protected $queue,
        protected array $payload,
    ) {
        // empty
    }

    /**
     * @return array<mixed>
     */
    public function payload(): array {
        return $this->payload;
    }

    public function getJobId(): string|null {
        return $this->payload()['id'] ?? null;
    }

    public function getRawBody(): string {
        return json_encode($this->payload());
    }

    public function attempts(): int {
        return ($this->payload()['attempts'] ?? 0) + 1;
    }
}
