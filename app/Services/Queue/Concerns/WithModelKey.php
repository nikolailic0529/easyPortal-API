<?php declare(strict_types = 1);

namespace App\Services\Queue\Concerns;

use LastDragon_ru\LaraASP\Queue\Concerns\WithInitialization;

/**
 * @mixin WithInitialization
 */
trait WithModelKey {
    /**
     * @private
     */
    protected string $modelKey;

    public function getModelKey(): string {
        return $this->modelKey;
    }

    public function uniqueId(): string {
        return $this->getModelKey();
    }

    public function init(string $key): static {
        // Initialize
        $this->modelKey = $key;

        $this->initialized();

        // Return
        return $this;
    }
}
