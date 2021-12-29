<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

/**
 * @deprecated
 */
class UpdateOrgRoles {
    public function __construct(
        protected UpdateOrgRole $updateOrgRole,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        $updated = [];

        foreach ($args['input'] as $roleInput) {
            $updated[] = ($this->updateOrgRole)(null, [
                'input' => $roleInput,
            ])['updated'];
        }

        return ['updated' => $updated];
    }
}
