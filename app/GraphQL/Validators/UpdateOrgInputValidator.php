<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use App\Rules\Color;
use App\Rules\CurrencyId;
use App\Rules\Image;
use App\Rules\Locale;
use Nuwave\Lighthouse\Validation\Validator;

class UpdateOrgInputValidator extends Validator {
    public function __construct(
        protected CurrencyId $currencyId,
        protected Locale $locale,
        protected Color $color,
        protected Image $image,
    ) {
        // empty
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array {
        return [
            'locale'                     => ['nullable', $this->locale],
            'currency_id'                => ['nullable', $this->currencyId],
            'website_url'                => ['nullable', 'url'],
            'email'                      => ['nullable', 'email'],
            'timezone'                   => ['nullable', 'timezone'],
            'analytics_code'             => ['nullable'],
            'branding.dark_theme'        => ['nullable'],
            'branding.main_color'        => ['nullable', $this->color],
            'branding.secondary_color'   => ['nullable', $this->color],
            'branding.logo_url'          => ['nullable', $this->image],
            'branding.favicon_url'       => ['nullable', $this->image],
            'branding.welcome_image_url' => ['nullable', $this->image],
            'branding.welcome_heading'   => ['nullable'],
            'branding.welcome_underline' => ['nullable'],
        ];
    }
}
