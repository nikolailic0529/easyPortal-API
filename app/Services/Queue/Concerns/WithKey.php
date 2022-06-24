<?php declare(strict_types = 1);

namespace App\Services\Queue\Concerns;

use App\Services\Queue\Job;

/**
 * @mixin Job
 */
trait WithKey {
    /**
     * @private should be protected for serialization
     */
    protected string|int $key;

    public function getKey(): string|int {
        return $this->key;
    }

    protected function setKey(string|int $key): static {
        $this->key = $key;

        return $this;
    }

    public function uniqueId(): string {
        return (string) $this->getKey();
    }

    public function init(string|int $key): static {
        // Initialize
        $this->setKey($key);
        $this->initialized();

        // Return
        return $this;
    }
}
