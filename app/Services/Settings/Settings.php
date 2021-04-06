<?php declare(strict_types = 1);

namespace App\Services\Settings;

use Config\Constants;
use Illuminate\Contracts\Config\Repository;
use ReflectionClass;
use ReflectionClassConstant;

use function array_filter;

class Settings {
    public function __construct(
        protected Repository $config,
    ) {
        // empty
    }

    /**
     * @return array<\App\Services\Settings\Setting>
     */
    public function getEditableSettings(): array {
        return array_filter($this->getSettings(), static function (Setting $setting) {
            return !$setting->isInternal();
        });
    }

    /**
     * @return array<\App\Services\Settings\Setting>
     */
    protected function getSettings(): array {
        $store     = $this->getStore();
        $settings  = [];
        $constants = (new ReflectionClass($store))->getConstants(ReflectionClassConstant::IS_PUBLIC);

        foreach ($constants as $name => $value) {
            $settings[] = new Setting($this->config, new ReflectionClassConstant($store, $name));
        }

        return $settings;
    }

    /**
     * @return class-string
     */
    protected function getStore(): string {
        return Constants::class;
    }
}
