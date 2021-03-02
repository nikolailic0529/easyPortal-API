<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use App\Models\Model;
use Closure;

/**
 * @internal
 */
class ClosureKey implements KeyRetriever {
    protected Closure $closure;

    public function __construct(Closure $closure) {
        $this->closure = $closure;
    }

    public function get(Model $model): mixed {
        return ($this->closure)($model);
    }
}
