<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Exceptions\HasErrorCode;
use App\Exceptions\TranslatedException;
use Exception;
use Throwable;

use function __;

class UpdateOrgUserPasswordInvalidToken extends Exception implements TranslatedException {
    use HasErrorCode;

    public function __construct(Throwable $previous = null) {
        parent::__construct('Invalid invite token.', 0, $previous);
    }

    public function getErrorMessage(): string {
        return __('graphql.mutations.updateOrgUserPassword.invalid_token');
    }
}
