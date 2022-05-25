<?php declare(strict_types = 1);

namespace App\Services\Events\Eloquent;

use Illuminate\Database\Eloquent\Model;

interface OnModelSaved {
    public function modelSaved(Model $model): void;
}
