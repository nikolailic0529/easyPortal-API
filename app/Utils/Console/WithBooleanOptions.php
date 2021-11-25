<?php declare(strict_types = 1);

namespace App\Utils\Console;

/**
 * @mixin \Illuminate\Console\Command
 */
trait WithBooleanOptions {
    protected function getBooleanOption(string $name, bool $default): bool {
        $noName = "no-{$name}";

        if ($this->hasOption($noName) && $this->option($noName)) {
            return false;
        }

        if ($this->option($name)) {
            return true;
        }

        return $default;
    }
}
