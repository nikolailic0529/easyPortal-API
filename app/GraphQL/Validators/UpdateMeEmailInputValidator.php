<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use App\Rules\UniqueUserEmail;
use Nuwave\Lighthouse\Validation\Validator;

class UpdateMeEmailInputValidator extends Validator {
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        return [
            'email' => ['required', 'email', new UniqueUserEmail()],
        ];
    }
}
