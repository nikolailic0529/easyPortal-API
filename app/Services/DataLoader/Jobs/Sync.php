<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use LastDragon_ru\LaraASP\Queue\Contracts\Initializable;

abstract class Sync extends Job implements ShouldBeUnique, Initializable {
    abstract public function uniqueId(): string;
}
