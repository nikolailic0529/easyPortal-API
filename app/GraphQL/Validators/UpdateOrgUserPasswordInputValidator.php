<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use Nuwave\Lighthouse\Validation\Validator;

class UpdateOrgUserPasswordInputValidator extends Validator {
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        return [
            'password'   => ['required', 'confirmed'],
            'token'      => ['required', 'string'],
            'first_name' => ['required', 'string'],
            'last_name'  => ['required', 'string'],
        ];
    }
}
