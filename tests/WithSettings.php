<?php declare(strict_types = 1);

namespace Tests;

use Illuminate\Contracts\Config\Repository;

use function is_callable;

/**
 * @mixin TestCase
 *
 * @phpstan-type Settings         array<string,mixed>
 * @phpstan-type SettingsCallback callable(static): Settings
 * @phpstan-type SettingsFactory  SettingsCallback|Settings|null
 */
trait WithSettings {
    /**
     * @param SettingsFactory $settings
     */
    public function setSettings(callable|array|null $settings): void {
        if (is_callable($settings)) {
            $settings = $settings($this);
        }

        $config = $this->app->make(Repository::class);

        foreach ((array) $settings as $path => $value) {
            $config->set($path, $value);
        }
    }
}
