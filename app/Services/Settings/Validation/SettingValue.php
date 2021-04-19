<?php declare(strict_types = 1);

namespace App\Services\Settings\Validation;

use App\Services\Settings\Setting;
use App\Services\Settings\Settings;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;

use function __;
use function array_map;
use function explode;
use function implode;
use function trim;

class SettingValue implements Rule {
    protected Validator|null $validator = null;

    public function __construct(
        protected Factory $factory,
        protected Setting $setting,
    ) {
        // empty
    }

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        // Rules?
        $rules = $this->setting->getType()->getValidationRules();

        if (!$rules) {
            return true;
        }

        // Create validator
        $key = 'value';

        if ($this->setting->isArray()) {
            $key   = "{$key}.*";
            $value = explode(Settings::DELIMITER, $value);
            $value = array_map(static function (string $value): string {
                return trim($value);
            }, $value);
        }

        $this->validator = $this->factory->make(['value' => $value], [$key => $rules]);

        // Validate
        return !$this->validator->fails();
    }

    public function message(): string {
        $messages = $this->validator ? $this->validator->errors()->unique() : [];
        $messages = array_map(static function (string $message): string {
            return trim(trim($message, '.'));
        }, $messages);

        return __('validation.setting', [
            'setting'  => $this->setting->getName(),
            'messages' => implode(', ', $messages),
        ]);
    }
}