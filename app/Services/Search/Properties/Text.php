<?php declare(strict_types = 1);

namespace App\Services\Search\Properties;

class Text extends Value {
    public function getType(): string {
        return 'wildcard';
    }

    public function hasKeyword(): bool {
        return true;
    }
}
