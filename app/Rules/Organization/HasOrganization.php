<?php declare(strict_types = 1);

namespace App\Rules\Organization;

use App\GraphQL\Directives\Directives\Mutation\Rules\ContextAwareRuleImpl;
use App\Models\Organization;
use App\Models\OrganizationUser;

trait HasOrganization {
    use ContextAwareRuleImpl;

    protected function getContextOrganization(): ?Organization {
        $organization = $this->getMutationRoot();

        if ($organization instanceof OrganizationUser) {
            $organization = $organization->organization;
        }

        if (!($organization instanceof Organization)) {
            return null;
        }

        return $organization;
    }
}
