<?php declare(strict_types = 1);

namespace App\Services\I18n\Contracts;

interface HasTimezonePreference {
    /**
     * Get the preferred timezone of the entity.
     */
    public function preferredTimezone(): ?string;
}
