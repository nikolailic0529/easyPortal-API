<?php declare(strict_types = 1);

namespace App\GraphQL\Validators\Mutation;

use App\Models\Currency;
use App\Rules\Locale;
use Illuminate\Contracts\Config\Repository;
use Nuwave\Lighthouse\Validation\Validator;

use function implode;

class UpdateOrganizationValidator extends Validator {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        $currenciesTable = (new Currency())->getTable();
        $maxSize         = $this->config->get('ep.image.max_size');
        $formats         = $this->config->get('ep.image.formats');
        $mimeTypes       = implode(',', $formats);

        return [
            'input.locale'                   => ['nullable', new Locale() ],
            'input.currency_id'              => ['nullable', "exists:{$currenciesTable},id"],
            'input.branding_dark_theme'      => ['nullable', 'boolean'],
            'input.branding_primary_color'   => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'input.branding_secondary_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'input.website_url'              => ['nullable', 'url'],
            'input.email'                    => ['nullable', 'email'],
            'input.branding_logo'            => [
                'nullable',
                "mimes:{$mimeTypes}",
                "max:{$maxSize}",
            ],
            'input.branding_favicon'         => [
                'nullable',
                "mimes:{$mimeTypes}",
                "max:{$maxSize}",
            ],
        ];
    }
}
