<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use App\Rules\RoleId;
use Nuwave\Lighthouse\Validation\Validator;


class InviteOrgUserInputValidator extends Validator {

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        return [
            'email'   => ['required', 'email'],
            'role_id' => ['required', new RoleId()],
        ];
    }
}