<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use App\Rules\Org\RoleId;
use App\Rules\TeamId;
use Nuwave\Lighthouse\Validation\Validator;

use function app;

/**
 * @deprecated
 */
class InviteOrgUserInputValidator extends Validator {
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        return [
            'email'   => ['required', 'email'],
            'role_id' => ['required', app(RoleId::class)],
            'team_id' => ['nullable', new TeamId()],
        ];
    }
}
