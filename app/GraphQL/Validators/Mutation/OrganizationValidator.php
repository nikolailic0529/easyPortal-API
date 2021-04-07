<?php declare(strict_types = 1);

namespace App\GraphQL\Validators\Mutation;

use App\Models\Currency;
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
        $currenciesTable = (new Currency())->getTable();
        $maxSize         = $this->config->get('easyportal.image.max_size');
        $formats         = $this->config->get('easyportal.image.formats');
        $mimeTypes       = implode(',', $formats);

        return [
            'locale'                   => [ new Locale() ],
            'currency_id'              => ["exists:{$currenciesTable},id"],
            'branding_dark_theme'      => ['boolean'],
            'branding_primary_color'   => ['regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'branding_secondary_color' => ['regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
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
