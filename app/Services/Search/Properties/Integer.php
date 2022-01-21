<?php declare(strict_types = 1);

namespace App\Services\Search\Properties;

class Integer extends Value {
    public function getType(): string {
        return 'long';
    }
}
