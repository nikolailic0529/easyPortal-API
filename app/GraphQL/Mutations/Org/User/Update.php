<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org\User;

use App\GraphQL\Mutations\User\Organization\Update as UserOrganizationUpdate;
use App\GraphQL\Mutations\User\Organization\UpdateInput as UserOrganizationUpdateInput;
use App\GraphQL\Mutations\User\Update as UserUpdate;
use App\GraphQL\Mutations\User\UpdateInput as UserUpdateInput;
use App\Models\User;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Support\Arr;

class Update {
    public function __construct(
        protected CurrentOrganization $organization,
        protected UserUpdate $userMutation,
        protected UserOrganizationUpdate $userOrganizationMutation,
    ) {
        // empty
    }

    /**
     * @param array{input: array<mixed>} $args
     */
    public function __invoke(User $user, array $args): bool {
        $input                 = $args['input'];
        $properties            = ['enabled', 'role_id', 'team_id'];
        $userInput             = new UserUpdateInput(Arr::except($input, $properties));
        $organizationUserInput = new UserOrganizationUpdateInput(Arr::only($input, $properties));
        $organizationUser      = $user->organizations()
            ->where('organization_id', '=', $this->organization->getKey())
            ->firstOrFail();

        return $this->userMutation->update($user, $userInput)
            && $this->userOrganizationMutation->update($organizationUser, $organizationUserInput);
    }
}
