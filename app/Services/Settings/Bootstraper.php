<?php declare(strict_types = 1);

namespace App\Services\Settings;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Date;
use LogicException;
use Psr\Log\LoggerInterface;
use Throwable;

use function array_key_exists;
use function sprintf;

class Bootstraper extends Settings {
    protected const MARKER = '__ep_settings';

    public function __construct(
        Application $app,
        Repository $config,
        Storage $storage,
        protected LoggerInterface $logger,
    ) {
        parent::__construct($app, $config, $storage);
    }

    public function bootstrap(): void {
        try {
            $this->load();
        } catch (Throwable $exception) {
            $this->logger->emergency('Failed to load custom config file.', [
                'exception' => $exception,
            ]);

            if (!$this->config->get('ep.settings.recoverable')) {
                throw $exception;
            }
        }
    }

    protected function load(): void {
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
        $this->config->set(static::MARKER, Date::now()->getTimestamp());
    }

    protected function isBootstrapped(): bool {
        $cached   = Date::createFromTimestamp($this->config->get(static::MARKER, 0));
        $modified = $this->storage->getLastModified();

        return $modified && $cached >= $modified;
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
