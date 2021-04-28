<?php declare(strict_types = 1);

namespace App\Services\Tenant;

use App\Models\Organization;
use Illuminate\Contracts\Translation\HasLocalePreference;

interface Tenant extends HasLocalePreference {
    public function get(): Organization;

    public function getKey(): string;

    public function isRoot(): bool;

    public function is(Organization|string|null $tenant): bool;
}
