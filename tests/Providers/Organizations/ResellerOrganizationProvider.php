<?php declare(strict_types = 1);

namespace Tests\Providers\Organizations;

use App\Models\Enums\OrganizationType;

class ResellerOrganizationProvider extends OrganizationProvider {
    public function __construct(?string $id = null) {
        parent::__construct($id, OrganizationType::reseller());
    }
}
