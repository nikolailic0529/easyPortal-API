<?php declare(strict_types = 1);

namespace App\Services\Settings;

use Illuminate\Support\Env;

use function array_key_exists;

class Bootstraper extends Settings {
    protected const MARKER = '__settings_loaded';

    public function bootstrap(): void {
        // Loaded?
        if ($this->config->get(static::MARKER)) {
            return;
        }

        // Load
        $saved = $this->getSavedSettings();

        foreach ($this->getSettings() as $setting) {
            $path  = $setting->getPath();
            $value = $this->getCurrentValue($saved, $setting);

            $this->config->set($path, $value);
        }

        // Mark
        $this->config->set(static::MARKER, true);
    }

    /**
     * @param array<string, mixed> $saved
     */
    protected function getCurrentValue(array $saved, Setting $setting): mixed {
        if ($this->isOverridden($setting->getName())) {
            return $this->getValue($setting, Env::getRepository()->get($setting->getName()));
        }

        if ($this->isEditable($setting) && array_key_exists($setting->getName(), $saved)) {
            return $saved[$setting->getName()];
        }

        return $setting->getDefaultValue();
    }
}
