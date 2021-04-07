<?php declare(strict_types = 1);

namespace App\Services\Settings;

use Illuminate\Support\Env;

use function array_key_exists;

class Bootstraper extends Settings {
    public function bootstrap(): void {
        $saved = $this->getSavedSettings();

        foreach ($this->getSettings() as $setting) {
            $path  = $setting->getPath();
            $value = $this->getCurrentValue($saved, $setting);

            $this->config->set($path, $value);
        }
    }

    /**
     * @param array<string, mixed> $saved
     */
    protected function getCurrentValue(array $saved, Setting $setting): mixed {
        if ($this->isOverridden($setting->getName())) {
            return $this->getValue($setting, Env::getRepository()->get($setting->getName()));
        }

        if (array_key_exists($setting->getName(), $saved)) {
            return $saved[$setting->getName()];
        }

        return $setting->getDefaultValue();
    }
}
