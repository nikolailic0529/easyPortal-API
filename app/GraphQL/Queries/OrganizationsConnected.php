<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Reseller;

use function is_null;

class OrganizationsConnected {
    /**
     * @param array<string, mixed> $args
     *
     * @return array<string,mixed>
     */
    public function __invoke(Reseller $reseller, array $args): bool {
        return !is_null($reseller->organization);
    }
}
