<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Exceptions\HasErrorCode;
use App\Exceptions\TranslatedException;
use Exception;
use Throwable;

use function __;

class InviteOrgUserAlreadyUsedInvitation extends Exception implements TranslatedException {
    use HasErrorCode;

    public function __construct(Throwable $previous = null) {
        parent::__construct('User already used the invitation.', 0, $previous);
    }

    public function getErrorMessage(): string {
        return __('graphql.mutations.inviteOrgUser.user_already_used_invitation');
    }
}
