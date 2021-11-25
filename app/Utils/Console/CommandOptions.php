<?php declare(strict_types = 1);

namespace App\Utils\Console;

/**
 * @see \App\Services\DataLoader\Commands\Concerns\WithBooleanOptions
 */
trait CommandOptions {
    /**
     * @param array<string,mixed> $params
     * @param array<string,?bool> $options
     *
     * @return array<string,mixed>
     */
    protected function setBooleanOptions(array $params, array $options): array {
        foreach ($options as $name => $value) {
            $params = $this->setBooleanOption($params, $name, $value);
        }

        return $params;
    }

    /**
     * @param array<string,mixed> $params
     *
     * @return array<string,mixed>
     */
    protected function setBooleanOption(array $params, string $name, ?bool $value): array {
        if ($value !== null) {
            if ($value) {
                $params["--{$name}"] = true;
            } else {
                $params["--no-{$name}"] = true;
            }
        }

        return $params;
    }
}
