<?php declare(strict_types = 1);

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Validation\Rule;
use JetBrains\PhpStorm\ArrayShape;
use LastDragon_ru\LaraASP\Spa\Http\Request;
use LastDragon_ru\LaraASP\Spa\Validation\Rules\StringRule;

class SignupRequest extends Request {
    public function rules(Translator $translator): array {
        return [
            'given_name'  => ['required', new StringRule($translator), 'min:3', 'max:255'],
            'family_name' => ['required', new StringRule($translator), 'min:3', 'max:255'],
            'email'       => ['required', new StringRule($translator), 'min:3', 'max:255', 'email', Rule::unique(User::class, 'email')],
            'phone'       => ['required', new StringRule($translator), 'phone'],
            'company'     => ['required', new StringRule($translator), 'min:3', 'max:255'],
            'reseller'    => ['nullable', new StringRule($translator), 'min:3', 'max:255'],
        ];
    }

    #[ArrayShape([
        'given_name'  => 'string',
        'family_name' => 'string',
        'email'       => 'string',
        'phone'       => 'string',
        'company'     => 'string',
        'reseller'    => 'string',
    ])]
    public function validated() {
        return parent::validated(); // TODO: Change the autogenerated stub
    }
}
