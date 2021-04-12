<?php declare(strict_types = 1);

namespace App\Services\Settings;

use Illuminate\Support\Env;
use LogicException;

use function array_key_exists;
use function sprintf;

class Bootstraper extends Settings {
    protected const MARKER = '__settings_loaded';

    public function bootstrap(): void {
        // Loaded?
        if ($this->isBootstrapped()) {
            return;
        }

        // Load
        $saved = $this->getSavedSettings();

        foreach ($this->getSettings() as $setting) {
            // If config cached we must not set readonly vars
            if ($this->isCached() && $setting->isReadonly()) {
                continue;
            }

            // Set
            $path  = $setting->getPath();
            $value = $this->getCurrentValue($saved, $setting);

            $this->config->set($path, $value);
        }

        // Mark
        $this->config->set(static::MARKER, true);
    }

    protected function isBootstrapped(): bool {
        return (bool) $this->config->get(static::MARKER);
    }

    /**
     * @param array<string, mixed> $saved
     */
    protected function getCurrentValue(array $saved, Setting $setting): mixed {
        // - Config cached?
        //   - isReadonly? (overridden by env)
        //      => ignore (because config:cache will use value from .env)
        //   - no:
        //      => return saved (if editable and exists) or default
        // - no:
        //   - isReadonly? (overridden by env)
        //      => return value from .env
        //   - no:
        //      => return saved (if editable and exists) or default
        $value = null;

        if (!$this->isCached() && !$setting->isReadonly()) {
            if ($this->isEditable($setting) && array_key_exists($setting->getName(), $saved)) {
                $value = $saved[$setting->getName()];
            } else {
                $value = $setting->getDefaultValue();
            }
        } elseif (!$this->isCached()) {
            $value = $this->getEnvValue($setting);
        } else {
            throw new LogicException(sprintf(
                'Impossible to get current value for setting `%s`.',
                $setting->getName(),
            ));
        }

        return $value;
    }

    protected function getEnvValue(Setting $setting): mixed {
        return $this->getValue($setting, Env::getRepository()->get($setting->getName()));
    }
}
