<?php declare(strict_types = 1);

namespace App\GraphQL\Validators\Mutation;

use App\Models\User;
use Illuminate\Validation\Rule;
use Nuwave\Lighthouse\Validation\Validator;

class AuthSignUpValidator extends Validator {
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        return [
            'given_name'  => ['required', 'min:3', 'max:255'],
            'family_name' => ['required', 'min:3', 'max:255'],
            'email'       => [
                'required',
                'min:3',
                'max:255',
                'email',
                Rule::unique(User::class, 'email'),
            ],
            'phone'       => [
                'required',
                // FIXME [!] Enable 'phone',
                'regex:/\+[0-9]{8,15}/',
            ],
            'company'     => ['required', 'min:3', 'max:255'],
            'reseller'    => ['nullable', 'min:3', 'max:255'],
        ];
    }
}
