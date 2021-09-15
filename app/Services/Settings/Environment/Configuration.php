<?php declare(strict_types = 1);

namespace App\Services\Settings\Environment;

use App\Services\Settings\Settings;

class Configuration extends Settings {
    /**
     * @return array{envs: array<string,string>, config: array<string,mixed>}
     */
    public function getConfiguration(): array {
        $envs   = [];
        $config = [];

        foreach ($this->getSettings() as $setting) {
            // Name & Value
            $name  = $setting->getName();
            $value = $this->getValue($setting);

            // Setting
            if ($setting->getPath()) {
                $config[$setting->getPath()] = $value;
            }

            // Cache current ENV value
            if ($this->environment->has($name)) {
                $config[Environment::SETTING][$name] = $this->environment->get($name);
            }

            // Add ENV
            $envs[$name] = $this->serializeValue($setting, $value);
        }

        // Return
        return [
            'envs'   => $envs,
            'config' => $config,
        ];
    }
}
