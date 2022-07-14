<?php declare(strict_types = 1);

namespace App\Services\Auth\Contracts;

use App\Models\Organization;

interface HasPermissions {
    /**
     * @return array<string>
     */
    public function getPermissions(): array;

    /**
     * @param array<string> $permissions
     */
    public function setPermissions(array $permissions): bool;

    /**
     * @return array<string>
     */
    public function getOrganizationPermissions(Organization $organization): array;
}
