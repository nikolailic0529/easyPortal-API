<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use App\Rules\PermissionId;
use Nuwave\Lighthouse\Validation\Validator;

/**
 * @deprecated
 */
class UpdateOrgRolesInputValidator extends Validator {
    /**
     * @return array<mixed>
     */
    public function rules(): array {
        return [
            'name'          => ['nullable','string'],
            'permissions.*' => ['required', new PermissionId()],
        ];
    }
}
