<?php declare(strict_types = 1);

namespace App\Services\Settings;

use Illuminate\Support\Env;

use function array_key_exists;

/**
 * @internal
 */
class Config extends Settings {
    /**
     * @return array<string,mixed>
     */
    public function getConfig(): array {
        $saved  = $this->getSavedSettings();
        $config = [];

        foreach ($this->getSettings() as $setting) {
            $config[$setting->getPath()] = $this->getValue($saved, $setting);
        }

        return $config;
    }

    /**
     * @return array<string,mixed>
     */
    public function getEnvVars(): array {
        $saved  = $this->getSavedSettings();
        $config = [];

        foreach ($this->getSettings() as $setting) {
            $config[$setting->getName()] = $this->serializeValue($setting, $this->getValue($saved, $setting));
        }

        return $config;
    }

    /**
     * @param array<string, mixed> $saved
     */
    protected function getValue(array $saved, Setting $setting): mixed {
        // - isReadonly? (overridden by env)
        //   => return value from .env
        // - no:
        //   => return saved (if editable and exists) or default
        $value = null;

        if ($setting->isReadonly()) {
            $value = $this->getEnvValue($setting);
        } else {
            if ($this->isEditable($setting) && array_key_exists($setting->getName(), $saved)) {
                $value = $saved[$setting->getName()];
            } else {
                $value = $setting->getDefaultValue();
            }
        }

        return $value;
    }

    protected function getEnvValue(Setting $setting): mixed {
        return $this->parseValue($setting, Env::getRepository()->get($setting->getName()));
    }
}
