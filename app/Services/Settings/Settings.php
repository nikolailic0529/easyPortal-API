<?php declare(strict_types = 1);

namespace App\Services\Settings;

use App\Services\Settings\Exceptions\FailedToLoadEnv;
use App\Services\Settings\Jobs\ConfigUpdate;
use Config\Constants;
use Dotenv\Dotenv;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionClassConstant;

use function array_fill_keys;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_values;
use function explode;
use function file_get_contents;
use function is_null;
use function trim;

class Settings {
    public const    PATH      = 'app/settings.json';
    public const    DELIMITER = ',';
    protected const SECRET    = '********';

    /**
     * @var array<\App\Services\Settings\Setting>
     */
    private array $settings = [];

    /**
     * @var array<\App\Services\Settings\Setting>
     */
    private array $editable = [];

    /**
     * @var array<string, mixed>
     */
    private array $readonly;

    public function __construct(
        protected Application $app,
        protected Repository $config,
        protected Storage $storage,
    ) {
        // empty
    }

    /**
     * @deprecated ?
     */
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
            $value          = $this->parseValue($setting, $settings[$name]);
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
     * @return array<class-string<\App\Services\Queue\Job>>
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
     * @return array<class-string<\App\Services\Queue\CronJob>>
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
     * @return array<string,string>
     */
    public function getPublicSettings(): array {
        $settings = [];

        foreach ($this->getSettings() as $setting) {
            if ($setting->isPublic()) {
                $settings[$setting->getPublicName()] = $this->serializeValue($setting, $setting->getValue());
            }
        }

        return $settings;
    }

    /**
     * @return array<\App\Services\Settings\Setting>
     */
    protected function getSettings(): array {
        if (!$this->settings) {
            $store          = $this->getStore();
            $constants      = (new ReflectionClass($store))->getConstants(ReflectionClassConstant::IS_PUBLIC);
            $this->settings = [];

            foreach ($constants as $name => $value) {
                $this->settings[$name] = new Setting(
                    $this->config,
                    new ReflectionClassConstant($store, $name),
                    $this->isReadonly($name),
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

    protected function parseValue(Setting $setting, ?string $value): mixed {
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

    public function serializeValue(Setting $setting, mixed $value): string {
        // Secret?
        if ($setting->isSecret()) {
            if ($setting->isArray() && !is_null($value)) {
                $value = array_fill_keys(array_keys((array) $value), static::SECRET);
            } else {
                $value = $value ? static::SECRET : null;
            }
        }

        // Serialize
        $type   = $setting->getType();
        $string = null;

        if ($setting->isArray() && !is_null($value)) {
            $string = (new Collection((array) $value))
                ->map(static function (mixed $value) use ($type): string {
                    return $type->toString($value);
                })
                ->implode(self::DELIMITER);
        } else {
            $string = $type->toString($value);
        }

        // Return
        return $string;
    }

    /**
     * Determines if setting overridden by ENV var.
     */
    protected function isReadonly(string $name): bool {
        if (!isset($this->readonly)) {
            $this->readonly = $this->getReadonlySettings();
        }

        return array_key_exists($name, $this->readonly);
    }

    protected function isEditable(Setting $setting): bool {
        return !$setting->isInternal();
    }

    /**
     * @return array<string,mixed>
     */
    protected function getReadonlySettings(): array {
        $path = "{$this->app->environmentPath()}/{$this->app->environmentFile()}";

        try {
            return Dotenv::parse(file_get_contents($path));
        } catch (Exception $exception) {
            throw new FailedToLoadEnv($path, $exception);
        }
    }

    /**
     * @return class-string
     */
    protected function getStore(): string {
        return Constants::class;
    }
}
