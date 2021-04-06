<?php declare(strict_types = 1);

namespace App\Services\Settings;

class Value extends Setting {
    public function __construct(
        protected Setting $setting,
        protected mixed $value,
    ) {
        parent::__construct($setting->config, $setting->constant);
    }

    public function getSetting(): Setting {
        return $this->setting;
    }

    public function getValue(): mixed {
        return $this->value;
    }
}
