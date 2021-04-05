<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

class StringScalar extends Type {
    public function getName(): string {
        return 'String';
    }
}
