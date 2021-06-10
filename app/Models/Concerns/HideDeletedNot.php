<?php declare(strict_types = 1);

namespace App\Models\Concerns;

/**
 * @mixin \App\Models\Model
 */
trait HideDeletedNot {
    protected function setDeletedNotAttribute(): void {
        // empty
    }

    protected function getDeletedNotAttribute(): mixed {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function setRawAttributes(array $attributes, $sync = false): static {
        unset($attributes['deleted_not']);

        return parent::setRawAttributes($attributes, $sync);
    }
}
