<?php declare(strict_types = 1);

namespace App\GraphQL\Validators\Mutation;

use App\Models\User;
use Closure;
use Nuwave\Lighthouse\Validation\Validator;

use function __;

class AuthSignInByPasswordValidator extends Validator {
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        return [
            'username' => [
                'required',
                'email',
                static function (string $attribute, mixed $value, Closure $fail): void {
                    // FIXME [!][Auth0] i18n
                    $user = User::whereEmail($value)->first();

                    if (!$user) {
                        $fail(__('auth.failed'));

                        return;
                    }
                },
            ],
            'password' => [
                'required',
            ],
        ];
    }
}
