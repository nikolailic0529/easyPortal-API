<?php declare(strict_types = 1);

namespace App\Services\Search\Properties;

class Boolean extends Value {
    public function getType(): string {
        return 'boolean';
    }
}
