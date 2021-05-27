<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use App\Rules\Color;
use App\Rules\CurrencyId;
use App\Rules\Locale;
use Illuminate\Contracts\Config\Repository;
use Nuwave\Lighthouse\Validation\Validator;

use function implode;

class UpdateOrganizationInputValidator extends Validator {
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
        $upload    = [
            "mimes:{$mimeTypes}",
            "max:{$maxSize}",
        ];

        return [
            'locale'                     => ['nullable', new Locale()],
            'currency_id'                => ['nullable', new CurrencyId()],
            'website_url'                => ['nullable', 'url'],
            'email'                      => ['nullable', 'email'],
            'analytics_code'             => ['nullable'],
            'branding.dark_theme'        => ['nullable'],
            'branding.main_color'        => ['nullable', new Color()],
            'branding.secondary_color'   => ['nullable', new Color()],
            'branding.logo'              => [...$upload],
            'branding.favicon'           => ['nullable', ...$upload],
            'branding.welcome_image'     => ['nullable', ...$upload],
            'branding.welcome_heading'   => ['nullable'],
            'branding.welcome_underline' => ['nullable'],
        ];
    }
}
