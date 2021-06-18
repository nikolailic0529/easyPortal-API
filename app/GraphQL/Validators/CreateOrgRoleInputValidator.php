<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use Nuwave\Lighthouse\Validation\Validator;

class CreateOrgRoleInputValidator extends Validator {
    /**
     * @return array<mixed>
     */
    public function rules(): array {
        return [
            'name' => ['required','string'],
        ];
    }
}
