<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

interface OwnedByOrganization {
    public function getOrganizationColumn(): string;
}
