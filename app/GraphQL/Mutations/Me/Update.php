<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\GraphQL\Mutations\User\Organization\Update as UserOrganizationUpdate;
use App\GraphQL\Mutations\User\Organization\UpdateInput as UserOrganizationUpdateInput;
use App\GraphQL\Mutations\User\Update as UserUpdate;
use App\GraphQL\Mutations\User\UpdateInput as UserUpdateInput;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\Auth\Auth;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Support\Arr;

class Update {
    public function __construct(
        protected Auth $auth,
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
        $input        = $args['input'];
        $properties   = ['team_id'];
        $userInput    = new UserUpdateInput(Arr::except($input, $properties));
        $orgUserInput = new UserOrganizationUpdateInput(Arr::only($input, $properties));
        $orgUser      = null;

        if (!$this->auth->isRoot($user)) {
            $orgUser = $user->organizations->first(function (OrganizationUser $orgUser): bool {
                return $orgUser->organization_id === $this->organization->getKey();
            });
        }

        return $this->userMutation->update($user, $userInput)
            && ($orgUser === null || $this->userOrganizationMutation->update($orgUser, $orgUserInput));
    }
}
