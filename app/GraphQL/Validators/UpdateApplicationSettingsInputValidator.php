<?php declare(strict_types = 1);

namespace App\GraphQL\Validators;

use App\Services\Settings\Settings;
use App\Services\Settings\Validation\SettingValue;
use Illuminate\Contracts\Validation\Factory;
use Nuwave\Lighthouse\Validation\Validator;

class UpdateApplicationSettingsInputValidator extends Validator {
    public function __construct(
        protected Factory $validator,
        protected Settings $settings,
    ) {
        // empty
    }

    /**
     * @return array<mixed>
     */
    public function rules(): array {
        $name    = $this->arg('name');
        $setting = $this->settings->getEditableSetting($name);

        return $setting ? [
            'value' => [new SettingValue($this->validator, $setting)],
        ] : [];
    }
}
