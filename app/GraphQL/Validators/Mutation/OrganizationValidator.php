<?php declare(strict_types = 1);

namespace App\GraphQL\Validators\Mutation;

use App\Models\Currency;
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
        $maxSize         = $this->config->get('branding.max_image_size');
        $formats         = $this->config->get('branding.image_formats');
        $mimeTypes       = implode(',', $formats);

        return [
            'locale'                      => [ 'regex:/^[a-z]{2}(?:_[A-Z]{2})?$/' ],
            'currency_id'                 => ["exists:{$currenciesTable},id"],
            'branding_is_dark_theme_mode' => ['boolean'],
            'branding_primary_color'      => ['regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'branding_secondary_color'    => ['regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'branding_logo'               => [
                'nullable',
                "mimes:{$mimeTypes}",
                "max:{$maxSize}",
            ],
            'branding_fav_icon'           => [
                'nullable',
                "mimes:{$mimeTypes}",
                "max:{$maxSize}",
            ],
        ];
    }
}
