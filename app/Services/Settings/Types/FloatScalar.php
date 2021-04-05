<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

class FloatScalar extends Type {
    public function getName(): string {
        return 'Float';
    }
}
