<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use App\Models\User;
use Nuwave\Lighthouse\Validation\Validator;

class UpdateMeEmailInputValidator extends Validator {
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        $table = (new User())->getTable();
        return [
            'email' => ['required', 'email', "unique:{$table},email"],
        ];
    }
}
