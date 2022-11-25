<?php declare(strict_types = 1);

namespace App\Services\Audit\Contracts;

use App\Services\Audit\Traits\AuditableImpl;
use App\Utils\Eloquent\Model;

/**
 * @see AuditableImpl
 *
 * @mixin Model
 */
interface Auditable {
    /**
     * @return array<string, array{added: array<string|int>, deleted: array<string|int>}>
     */
    public function getDirtyRelations(): array;
}
