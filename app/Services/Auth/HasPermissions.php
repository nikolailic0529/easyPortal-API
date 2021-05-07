<?php declare(strict_types = 1);

namespace App\Services\Auth;

interface HasPermissions {
    /**
     * @return array<string>
     */
    public function getPermissions(): array;
}
