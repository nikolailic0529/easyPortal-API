<?php declare(strict_types = 1);

namespace App\Services\Auth\Contracts;

use App\Models\Organization;

interface Enableable {
    public function isEnabled(?Organization $organization): bool;
}
