<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Testing;

use App\Models\Casts\DocumentPrice as Cast;
use Illuminate\Database\Eloquent\Model;

class DocumentPrice extends Cast {
    protected function isVisible(Model $model): bool {
        return true;
    }
}
