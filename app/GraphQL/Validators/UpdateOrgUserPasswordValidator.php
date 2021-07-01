<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use App\Rules\Color;
use App\Rules\CurrencyId;
use App\Rules\Locale;
use Illuminate\Contracts\Config\Repository;
use Nuwave\Lighthouse\Validation\Validator;

use function implode;

class UpdateOrgUserPasswordValidator extends Validator {
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        return [
            'password' => ['required', 'confirmed'],
        ];
    }
}
