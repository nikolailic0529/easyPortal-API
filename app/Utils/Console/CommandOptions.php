<?php declare(strict_types = 1);

namespace App\Utils\Console;

use function is_bool;

/**
 * @see \App\Utils\Console\WithBooleanOptions
 */
trait CommandOptions {
    /**
     * @param array<string,mixed> $options
     *
     * @return array<string,mixed>
     */
    protected function getOptions(array $options): array {
        $processed = [];

        foreach ($options as $name => $value) {
            if (is_bool($value)) {
                $processed = $this->setBooleanOption($processed, $name, $value);
            } elseif ($value !== null) {
                $processed[$name] = $value;
            } else {
                // empty
            }
        }

        return $processed;
    }

    /**
     * @param array<string,mixed> $options
     *
     * @return array<string,mixed>
     */
    protected function setBooleanOption(array $options, string $name, bool $value): array {
        if ($value) {
            $options["--{$name}"] = true;
        } else {
            $options["--no-{$name}"] = true;
        }

        return $options;
    }
}
