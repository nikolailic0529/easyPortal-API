<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use App\Rules\OrgRoleId;
use App\Rules\OrgUserEmailFree;
use Nuwave\Lighthouse\Validation\Validator;


class InviteOrgUserInputValidator extends Validator {

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        return [
            'email'   => ['required', 'email', new OrgUserEmailFree()],
            'role_id' => ['required', new OrgRoleId()],
        ];
    }
}
