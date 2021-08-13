<?php declare(strict_types = 1);

namespace App\Services\Search\Properties;

class Double extends Property {
    public function getType(): string {
        return 'double';
    }
}
