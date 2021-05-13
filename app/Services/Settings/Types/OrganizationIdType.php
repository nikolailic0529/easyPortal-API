<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

use App\Rules\OrganizationId;

class OrganizationIdType extends Type {
    /**
     * @inheritDoc
     */
    public function getValidationRules(): array {
        return [new OrganizationId()];
    }
}
