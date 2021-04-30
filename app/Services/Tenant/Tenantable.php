<?php declare(strict_types = 1);

namespace App\Services\Tenant;

use App\Models\Organization;

interface Tenantable {
    public function getOrganization(): ?Organization;
}
