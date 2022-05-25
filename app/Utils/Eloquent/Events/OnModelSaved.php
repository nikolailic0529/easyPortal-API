<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Events;

use Illuminate\Database\Eloquent\Model;

interface OnModelSaved {
    public function modelSaved(Model $model): void;
}
