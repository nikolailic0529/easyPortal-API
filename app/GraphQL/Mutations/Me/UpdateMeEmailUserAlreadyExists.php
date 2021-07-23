<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\Exceptions\HasErrorCode;
use App\Exceptions\TranslatedException;
use Exception;
use Throwable;

use function __;

class UpdateMeEmailUserAlreadyExists extends Exception implements TranslatedException {
    use HasErrorCode;

    public function __construct(string $email, Throwable $previous = null) {
        parent::__construct("Email {$email} already taken.", 0, $previous);
    }

    public function getErrorMessage(): string {
        return __('graphql.mutations.updateMeEmail.user_already_exists');
    }
}