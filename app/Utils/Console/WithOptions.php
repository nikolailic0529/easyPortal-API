<?php declare(strict_types = 1);

namespace App\Utils\Console;

use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;

use function filter_var;
use function is_string;

use const FILTER_VALIDATE_INT;

/**
 * @mixin Command
 */
trait WithOptions {
    protected function getBoolOption(string $name, bool $default = null): ?bool {
        $noName = "no-{$name}";

        if ($this->hasOption($noName) && $this->option($noName)) {
            return false;
        }

        if ($this->option($name)) {
            return true;
        }

        return $default;
    }

    protected function getIntOption(string $name, int $default = null): ?int {
        $value = $this->hasOption($name) ? $this->option($name) : null;
        $value = filter_var($value, FILTER_VALIDATE_INT) !== false
            ? (int) $value
            : $default;

        return $value;
    }

    protected function getStringOption(string $name, string $default = null): ?string {
        $value = $this->hasOption($name) ? $this->option($name) : null;
        $value = is_string($value) ? $value : $default;

        return $value;
    }

    protected function getDateTimeOption(string $name, DateTimeInterface $default = null): ?DateTimeInterface {
        $value = $this->getStringOption($name);
        $value = Date::make($value) ?? $default;

        return $value;
    }
}
