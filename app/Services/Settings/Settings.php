<?php declare(strict_types = 1);

namespace App\Services\Settings;

use App\Services\Settings\Environment\Environment;
use App\Services\Settings\Jobs\ConfigUpdate;
use Config\Constants;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionClassConstant;

use function array_fill_keys;
use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function explode;
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

    public function __construct(
        protected Application $app,
        protected Repository $config,
        protected Storage $storage,
        protected Environment $environment,
    ) {
        // empty
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
                $settings[$setting->getPublicName()] = $this->getPublicValue($setting);
            }
        }

        return $settings;
    }

    protected function getValue(Setting $setting): mixed {
        // Value from .env has a bigger priority, if it is not set and the
        // setting is editable we try to use saved value or use default
        // otherwise.
        $value = $setting->getDefaultValue();

        if ($this->environment->has($setting->getName())) {
            $value = $this->parseValue($setting, $this->environment->get($setting->getName()));
        } elseif ($this->isEditable($setting)) {
            if ($this->storage->has($setting->getName())) {
                $value = $this->storage->get($setting->getName());
            }
        } else {
            // empty
        }

        return $value;
    }

    public function getPublicValue(Setting $setting): string {
        // The most actual value is stored in the config, so we are trying to
        // use it if possible.
        $value = null;

        if ($setting->getPath()) {
            $value = $this->config->get($setting->getPath());
        } else {
            $value = $this->getValue($setting);
        }

        return $this->serializePublicValue($setting, $value);
    }

    public function getPublicDefaultValue(Setting $setting): string {
        return $this->serializePublicValue($setting, $setting->getDefaultValue());
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
                    new ReflectionClassConstant($store, $name),
                    $this->isReadonly($name),
                );
            }
        }

        return $this->settings;
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
        $stored   = (new Collection($this->storage->load()))
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

    protected function serializeValue(Setting $setting, mixed $value): string {
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

    protected function serializePublicValue(Setting $setting, mixed $value): string {
        if ($setting->isSecret()) {
            if ($setting->isArray() && !is_null($value)) {
                $value = array_fill_keys(array_keys((array) $value), static::SECRET);
            } else {
                $value = $value ? static::SECRET : null;
            }
        }

        return $this->serializeValue($setting, $value);
    }

    /**
     * Determines if setting overridden by ENV var.
     */
    protected function isReadonly(string $name): bool {
        return $this->environment->has($name);
    }

    protected function isEditable(Setting $setting): bool {
        return !$setting->isInternal();
    }

    /**
     * @return class-string
     */
    protected function getStore(): string {
        return Constants::class;
    }
}
