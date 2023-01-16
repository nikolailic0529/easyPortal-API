<?php declare(strict_types = 1);

namespace App\Services\Audit\Contracts;

use App\Services\Audit\Traits\AuditableImpl;
use App\Services\Auth\Permissions\Administer;
use App\Utils\Eloquent\Model;

/**
 * Marks that the changes of the Model should be stored in Audit Log.
 *
 * @see AuditableImpl
 *
 * @mixin Model
 */
interface Auditable {
    /**
     * @return array<string, array{type: string, added: array<string|int>, deleted: array<string|int>}>
     */
    public function getDirtyRelations(): array;

    /**
     * Returns the array of internal attributes names that's values should be
     * visible only to `administer` users.
     *
     * @see Administer
     *
     * @return array<string>
     */
    public function getInternalAttributes(): array;
}
