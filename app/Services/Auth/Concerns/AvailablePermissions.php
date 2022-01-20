<?php declare(strict_types = 1);

namespace App\Services\Auth\Concerns;

use App\Models\Organization;
use App\Services\Auth\Auth;
use App\Services\Auth\Permission as AuthPermission;
use Illuminate\Support\Collection;

trait AvailablePermissions {
    abstract protected function getAuth(): Auth;

    /**
     * @return array<string>
     */
    protected function getAvailablePermissions(Organization $organization): array {
        return (new Collection($this->getAuth()->getAvailablePermissions($organization)))
            ->map(static function (AuthPermission $permission): string {
                return $permission->getName();
            })
            ->all();
    }
}
