<?php declare(strict_types = 1);

namespace App\Models\Concerns\Audits;

/**
 * @mixin \App\Models\Model
 */
interface Auditable {
    /**
     * @return array<string>
     */
    public function getAuditableExcludedAttributes(): array;
}
