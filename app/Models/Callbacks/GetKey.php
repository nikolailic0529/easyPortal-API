<?php declare(strict_types = 1);

namespace App\Models\Callbacks;

use App\Models\Model;
use App\Models\Pivot;

class GetKey {
    public function __invoke(Model|Pivot $model): string {
        return $model->getKey();
    }
}
