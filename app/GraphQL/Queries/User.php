<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\User as UserModel;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Support\Collection;

class User {
    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    public function invitations(UserModel $user): Collection {
        return $user->invitations()
            ->where('organization_id', '=', $this->organization->getKey())
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
