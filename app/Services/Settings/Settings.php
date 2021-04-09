<?php declare(strict_types = 1);

namespace App\Services\Settings;

use App\Disc;
use App\Services\Settings\Exceptions\SettingsFailedToSave;
use App\Services\Settings\Jobs\ConfigUpdate;
use Config\Constants;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Support\Collection;
use Illuminate\Support\Env;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionClassConstant;

use function array_filter;
use function array_map;
use function array_values;
use function explode;
use function is_null;
use function json_decode;
use function json_encode;
use function json_last_error;

use const JSON_ERROR_NONE;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_LINE_TERMINATORS;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class Settings {
    public const DELIMITER = ',';

    public function __construct(
        protected Application $app,
        protected Repository $config,
        protected LoggerInterface $logger,
    ) {
        // empty
    }

    public function isCached(): bool {
        return $this->app instanceof CachesConfiguration
            && $this->app->configurationIsCached();
    }

    /**
     * @return array<\App\Services\Settings\Setting>
     */
    public function getEditableSettings(): array {
        return array_filter($this->getSettings(), function (Setting $setting) {
            return $this->isEditable($setting);
        });
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
        $store     = $this->getStore();
        $settings  = [];
        $constants = (new ReflectionClass($store))->getConstants(ReflectionClassConstant::IS_PUBLIC);

        foreach ($constants as $name => $value) {
            $settings[] = new Setting(
                $this->config,
                new ReflectionClassConstant($store, $name),
                $this->isOverridden($name),
            );
        }

        return $settings;
    }

    /**
     * @return array<string, string>
     */
    protected function getSavedSettings(): array {
        $fs       = $this->getDisc()->filesystem();
        $error    = null;
        $settings = [];

        try {
            if ($fs->exists($this->getFile())) {
                $settings = (array) json_decode($fs->get($this->getFile()), true);
            }

            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = json_last_error();
            }
        } catch (Exception $exception) {
            $error    = $exception;
            $settings = [];
        }

        if (!is_null($error)) {
            $this->logger->warning(
                'Impossible to load application settings. Default used.',
                [
                    'disc'  => $this->getDisc()->getValue(),
                    'file'  => $this->getFile(),
                    'error' => $error,
                ],
            );
        }

        return $settings;
    }

    /**
     * @param array<string, \App\Services\Settings\Value> $settings
     *
     * @throws \App\Services\Settings\Exceptions\SettingsFailedToSave if failed
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
        $fs      = $this->getDisc()->filesystem();
        $error   = null;
        $success = false;

        try {
            $success = $fs->put($this->getFile(), json_encode(
                $stored,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS,
            ));
        } catch (Exception $exception) {
            $error   = $exception;
            $success = false;
        }

        if (!$success) {
            throw new SettingsFailedToSave($error);
        }

        // Return
        return true;
    }

    protected function getValue(Setting $setting, ?string $value): mixed {
        $type   = $setting->getType();
        $result = $value;

        if (is_null($value) || $type->isNull($value)) {
            $result = null;
        } elseif ($setting->isArray()) {
            $result = explode(self::DELIMITER, $value);
            $result = array_map(static function (string $value) use ($type): mixed {
                return $type->fromString($value);
            }, $result);
        } else {
            $result = $type->fromString($value);
        }

        return $result;
    }

    /**
     * Determines if setting overridden by ENV var (this is possible only if the
     * application doesn't use cached config).
     */
    protected function isOverridden(string $name): bool {
        return !$this->isCached() && Env::getRepository()->has($name);
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

    protected function getDisc(): Disc {
        return Disc::app();
    }

    protected function getFile(): string {
        return 'settings.json';
    }
}
