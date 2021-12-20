<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Psr\Log\LogLevel as PsrLogLevel;
use ReflectionClass;

class LogLevel extends Type {
    public function getValues(): Collection|array|null {
        $interface = new ReflectionClass(PsrLogLevel::class);
        $constants = $interface->getConstants();
        $values    = [];

        foreach ($constants as $value) {
            $values[] = $value;
        }

        return $values;
    }

    /**
     * @inheritDoc
     */
    public function getValidationRules(): array {
        return [Rule::in($this->getValues())];
    }
}
