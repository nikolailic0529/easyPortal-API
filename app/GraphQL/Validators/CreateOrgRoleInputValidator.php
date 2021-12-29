<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use App\Rules\PermissionId;
use Nuwave\Lighthouse\Validation\Validator;

/**
 * @deprecated
 */
class CreateOrgRoleInputValidator extends Validator {
    /**
     * @return array<mixed>
     */
    public function rules(): array {
        return [
            'name'          => ['required','string'],
            'permissions.*' => ['required', new PermissionId()],
        ];
    }
}
