<?php declare(strict_types = 1);

namespace App\GraphQL\Validators\Mutation;

use App\Rules\CurrencyId;
use App\Rules\Locale;
use Illuminate\Contracts\Config\Repository;
use Nuwave\Lighthouse\Validation\Validator;

use function implode;

class OrganizationValidator extends Validator {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        $maxSize   = $this->config->get('ep.image.max_size');
        $formats   = $this->config->get('ep.image.formats');
        $mimeTypes = implode(',', $formats);

        return [
            'locale'                   => [new Locale() ],
            'currency_id'              => ['nullable', new CurrencyId()],
            'branding_dark_theme'      => ['nullable', 'boolean'],
            'branding_primary_color'   => ['nullable', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'branding_secondary_color' => ['nullable', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'website_url'              => ['url'],
            'email'                    => ['email'],
            'branding_logo'            => [
                'nullable',
                "mimes:{$mimeTypes}",
                "max:{$maxSize}",
            ],
            'branding_favicon'         => [
                'nullable',
                "mimes:{$mimeTypes}",
                "max:{$maxSize}",
            ],
        ];
    }
}
