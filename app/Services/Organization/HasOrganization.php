<?php declare(strict_types = 1);

namespace App\Services\Organization;

use App\Models\Organization;

interface HasOrganization {
    public function getOrganization(): ?Organization;
}
