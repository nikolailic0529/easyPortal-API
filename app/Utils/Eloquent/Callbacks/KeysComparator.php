<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Callbacks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class KeysComparator {
    /**
     * @template T of \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\Pivot
     *
     * @param T $a
     * @param T $b
     */
    public function __invoke(Model|Pivot $a, Model|Pivot $b): int {
        return $a->getKey() <=> $b->getKey();
    }
}
