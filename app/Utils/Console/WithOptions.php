<?php declare(strict_types = 1);

namespace App\Utils\Console;

use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Facades\Date;
use Symfony\Component\Console\Exception\RuntimeException;

use function assert;
use function filter_var;
use function is_string;

use const FILTER_VALIDATE_INT;

/**
 * @mixin Command
 */
trait WithOptions {
    protected function getIdArgument(string $name): string {
        $value     = $this->hasArgument($name) ? $this->argument($name) : null;
        $validator = $this->laravel->make(Factory::class)->make(
            [$name => $value],
            [$name => 'required|string|uuid'],
        );

        if ($validator->fails()) {
            throw new RuntimeException($validator->errors()->first());
        }

        assert(is_string($value));

        return $value;
    }

    /**
     * @return ($default is string ? string : string|null)
     */
    protected function getStringArgument(string $name, string $default = null): ?string {
        $value = $this->hasArgument($name) ? $this->argument($name) : null;
        $value = is_string($value) ? $value : $default;

        return $value;
    }

    /**
     * @return ($default is bool ? bool : bool|null)
     */
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

    /**
     * @return ($default is int ? int : int|null)
     */
    protected function getIntOption(string $name, int $default = null): ?int {
        $value = $this->hasOption($name) ? $this->option($name) : null;
        $value = filter_var($value, FILTER_VALIDATE_INT) !== false
            ? (int) $value
            : $default;

        return $value;
    }

    /**
     * @return ($default is string ? string : string|null)
     */
    protected function getStringOption(string $name, string $default = null): ?string {
        $value = $this->hasOption($name) ? $this->option($name) : null;
        $value = is_string($value) ? $value : $default;

        return $value;
    }

    /**
     * @return ($default is DateTimeInterface ? DateTimeInterface : DateTimeInterface|null)
     */
    protected function getDateTimeOption(string $name, DateTimeInterface $default = null): ?DateTimeInterface {
        $value = $this->getStringOption($name);
        $value = Date::make($value) ?? $default;

        return $value;
    }
}
