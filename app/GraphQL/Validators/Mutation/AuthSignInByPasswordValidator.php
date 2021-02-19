<?php declare(strict_types = 1);

namespace App\GraphQL\Validators\Mutation;

use Nuwave\Lighthouse\Validation\Validator;

class AuthSignInByPasswordValidator extends Validator {
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        return [
            'username' => [
                'required',
                'email',
            ],
            'password' => [
                'required',
            ],
        ];
    }
}
