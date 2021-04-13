<?php declare(strict_types = 1);

namespace App\Services\Settings;

use App\Services\Settings\Jobs\ConfigUpdate;
use Config\Constants;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Support\Collection;
use Illuminate\Support\Env;
use ReflectionClass;
use ReflectionClassConstant;

use function array_filter;
use function array_map;
use function array_values;
use function explode;
use function is_null;
use function trim;

class Settings {
    public const DELIMITER = ',';

    /**
     * @var array<\App\Services\Settings\Setting>
     */
    private array $settings = [];

    /**
     * @var array<\App\Services\Settings\Setting>
     */
    private array $editable = [];

    public function __construct(
        protected Application $app,
        protected Repository $config,
        protected Storage $storage,
    ) {
        // empty
    }

    public function isCached(): bool {
        return $this->app instanceof CachesConfiguration
            && $this->app->configurationIsCached();
    }

    public function getEditableSetting(string $name): ?Setting {
        return $this->getEditableSettings()[$name] ?? null;
    }

    /**
     * @return array<\App\Services\Settings\Setting>
     */
    public function getEditableSettings(): array {
        if (!$this->editable) {
            $this->editable = array_filter($this->getSettings(), function (Setting $setting) {
                return $this->isEditable($setting);
            });
        }

        return $this->editable;
    }

    /**
     * @param array{name: string, value: string} $settings
     *
     * @return array<\App\Services\Settings\Setting> Updated settings
     */
    public function setEditableSettings(array $settings): array {
        // Update
        /** @var array<\App\Services\Settings\Value> $updated */
        $updated  = [];
        $editable = $this->getEditableSettings();
        $settings = (new Collection($settings))
            ->filter(static function (array $setting): bool {
                return isset($setting['name']) && isset($setting['value']);
            })
            ->keyBy(static function (array $setting): string {
                return $setting['name'];
            })
            ->map(static function (array $setting): string {
                return $setting['value'];
            });

        foreach ($editable as $setting) {
            // Readonly?
            if ($setting->isReadonly()) {
                continue;
            }

            // Passed?
            if (!isset($settings[$setting->getName()])) {
                continue;
            }

            // Changed?
            $name           = $setting->getName();
            $value          = $this->getValue($setting, $settings[$name]);
            $updated[$name] = new Value($setting, $value);
        }

        // Save
        if ($updated) {
            $this->saveSettings($updated);

            $this->app->make(ConfigUpdate::class)->dispatch();
        }

        // Return
        return array_values($updated);
    }

    /**
     * @return array<class-string<\LastDragon_ru\LaraASP\Queue\Queueables\Job>>
     */
    public function getJobs(): array {
        $settings = $this->getSettings();
        $jobs     = [];

        foreach ($settings as $setting) {
            if ($setting->isJob()) {
                $jobs[] = $setting->getJob();
            }
        }

        return $jobs;
    }

    /**
     * @return array<class-string<\LastDragon_ru\LaraASP\Queue\Queueables\CronJob>>
     */
    public function getServices(): array {
        $settings = $this->getSettings();
        $services = [];

        foreach ($settings as $setting) {
            if ($setting->isService()) {
                $services[] = $setting->getService();
            }
        }

        return $services;
    }

    /**
     * @return array<\App\Services\Settings\Setting>
     */
    protected function getSettings(): array {
        if (!$this->settings) {
            // We need to load `.env` to determine readonly settings.
            $this->loadEnv();

            // Get list of the settings.
            $store          = $this->getStore();
            $constants      = (new ReflectionClass($store))->getConstants(ReflectionClassConstant::IS_PUBLIC);
            $this->settings = [];

            foreach ($constants as $name => $value) {
                $this->settings[$name] = new Setting(
                    $this->config,
                    new ReflectionClassConstant($store, $name),
                    $this->isOverridden($name),
                );
            }
        }

        return $this->settings;
    }

    /**
     * @return array<string, string>
     */
    protected function getSavedSettings(): array {
        return $this->storage->load();
    }

    /**
     * @param array<string, \App\Services\Settings\Value> $settings
     */
    protected function saveSettings(array $settings): bool {
        // Cleanup
        $editable = (new Collection($this->getEditableSettings()))
            ->filter(static function (Setting $setting): bool {
                return !$setting->isReadonly();
            })
            ->keyBy(static function (Setting $setting): string {
                return $setting->getName();
            });
        $settings = (new Collection($settings))
            ->keyBy(static function (Setting $setting): string {
                return $setting->getName();
            })
            ->intersectByKeys($editable);
        $stored   = (new Collection($this->getSavedSettings()))
            ->intersectByKeys($editable);

        // Update
        foreach ($settings as $setting) {
            $stored[$setting->getName()] = $setting->getValue();
        }

        // Save
        return $this->storage->save($stored->all());
    }

    protected function getValue(Setting $setting, ?string $value): mixed {
        $type   = $setting->getType();
        $result = $value;

        if (is_null($value)) {
            $result = null;
        } elseif ($setting->isArray()) {
            $result = explode(self::DELIMITER, $value);
            $result = array_map(static function (string $value) use ($type): mixed {
                return $type->fromString(trim($value));
            }, $result);
        } else {
            $result = $type->fromString(trim($value));
        }

        return $result;
    }

    /**
     * Determines if setting overridden by ENV var.
     */
    protected function isOverridden(string $name): bool {
        return Env::getRepository()->has($name);
    }

    protected function isEditable(Setting $setting): bool {
        return !$setting->isInternal();
    }

    protected function loadEnv(): void {
        (new EnvLoader())->load($this->app);
    }

    /**
     * @return class-string
     */
    protected function getStore(): string {
        return Constants::class;
    }
}
