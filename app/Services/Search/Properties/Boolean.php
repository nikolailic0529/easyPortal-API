<?php declare(strict_types = 1);

namespace App\Services\Search\Properties;

class Boolean extends Property {
    public function getType(): string {
        return 'boolean';
    }
}
