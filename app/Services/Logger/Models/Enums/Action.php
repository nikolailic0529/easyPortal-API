<?php declare(strict_types = 1);

namespace App\Services\Logger\Models\Enums;

use LastDragon_ru\LaraASP\Eloquent\Enum;

class Action extends Enum {
    public static function queueJobDispatched(): static {
        return static::make('job.dispatched');
    }

    public static function queueJobRun(): static {
        return static::make('job.run');
    }
}
