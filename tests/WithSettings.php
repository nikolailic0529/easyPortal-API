<?php declare(strict_types = 1);

namespace Tests;

use Closure;
use Illuminate\Contracts\Config\Repository;

/**
 * @mixin \Tests\TestCase
 */
trait WithSettings {
    /**
     * @param \Closure|array<string,mixed>|null $settings
     */
    public function setSettings(Closure|array|null $settings): void {
        if ($settings instanceof Closure) {
            $settings = $settings($this);
        }

        $config = $this->app->make(Repository::class);

        foreach ((array) $settings as $path => $value) {
            $config->set($path, $value);
        }
    }
}
