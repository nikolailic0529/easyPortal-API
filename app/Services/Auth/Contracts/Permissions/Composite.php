<?php declare(strict_types = 1);

namespace App\Services\Auth\Contracts\Permissions;

use App\Services\Auth\Permission;

/**
 * Indicates that Permission also provides one or more additional permissions.
 */
interface Composite {
    /**
     * @return non-empty-array<Permission>
     */
    public function getPermissions(): array;
}
