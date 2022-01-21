<?php declare(strict_types = 1);

namespace App\Services\Search\Properties;

class Date extends Value {
    public function getType(): string {
        return 'date';
    }
}
