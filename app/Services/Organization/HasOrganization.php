<?php declare(strict_types = 1);

namespace App\Services\Organization;

use App\Models\Organization;
use Illuminate\Support\Collection;

interface HasOrganization {
    public function getOrganization(): ?Organization;

    public function setOrganization(?Organization $organization): bool;

    /**
     * @return Collection<int, Organization>
     */
    public function getOrganizations(): Collection;
}
