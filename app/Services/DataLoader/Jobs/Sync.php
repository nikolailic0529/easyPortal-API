<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Utils\Console\CommandOptions;
use App\Utils\Console\CommandResult;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use LastDragon_ru\LaraASP\Queue\Contracts\Initializable;

abstract class Sync extends Job implements ShouldBeUnique, Initializable {
    use CommandOptions;
    use CommandResult;

    private string $objectId;

    protected function getObjectId(): string {
        return $this->objectId;
    }

    protected function setObjectId(string $objectId): static {
        $this->objectId = $objectId;

        return $this;
    }

    public function uniqueId(): string {
        return $this->getObjectId();
    }
}
