<?php declare(strict_types = 1);

namespace App;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use LastDragon_ru\LaraASP\Core\Enum;

class Disc extends Enum {
    public static function app(): static {
        return static::make('app');
    }

    public static function ui(): static {
        return static::make('ui');
    }

    public function filesystem(): Filesystem {
        return Storage::disk($this->getValue());
    }

    public function fake(): Filesystem {
        return Storage::fake($this->getValue());
    }

    public static function resources(): static {
        return static::make('resources');
    }
}
