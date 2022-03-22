<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Organization;
use App\Utils\Eloquent\Model;

/**
 * @mixin Model
 */
trait HasOrganization {
    use HasOrganizationNullable {
        setOrganizationAttribute as private setOrganizationAttributeNullable;
    }

    public function setOrganizationAttribute(Organization $organization): void {
        $this->setOrganizationAttributeNullable($organization);
    }
}
