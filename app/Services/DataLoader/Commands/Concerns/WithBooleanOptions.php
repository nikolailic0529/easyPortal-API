<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands\Concerns;

/**
 * @mixin \Illuminate\Console\Command
 */
trait WithBooleanOptions {
    protected function getBooleanOption(string $name, bool $default): bool {
        if ($this->option("no-{$name}")) {
            return false;
        }

        if ($this->option($name)) {
            return true;
        }

        return $default;
    }
}
