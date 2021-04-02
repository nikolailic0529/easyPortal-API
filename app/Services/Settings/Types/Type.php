<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

use ReflectionClass;

abstract class Type {
    public function getName(): string {
        return (new ReflectionClass($this))->getShortName();
    }
}
