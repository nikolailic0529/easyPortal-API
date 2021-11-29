<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Callbacks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class GetKey {
    public function __invoke(Model|Pivot $model): string|null {
        return $model->getKey();
    }
}
