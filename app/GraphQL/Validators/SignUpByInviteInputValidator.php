<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use Nuwave\Lighthouse\Validation\Validator;

/**
 * @deprecated
 */
class SignUpByInviteInputValidator extends Validator {
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        return [
            'password'    => ['required', 'string'],
            'token'       => ['required', 'string'],
            'given_name'  => ['required', 'string'],
            'family_name' => ['required', 'string'],
        ];
    }
}
