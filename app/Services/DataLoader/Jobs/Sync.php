<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Utils\Console\CommandOptions;
use App\Utils\Console\CommandResult;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use LastDragon_ru\LaraASP\Queue\Contracts\Initializable;

abstract class Sync extends Job implements ShouldBeUnique, Initializable {
    use CommandOptions;
    use CommandResult;

    protected string $objectId;
    /**
     * @deprecated
     * @var array<string,scalar>
     */
    protected array $arguments = [];

    protected function getObjectId(): string {
        return $this->objectId;
    }

    protected function setObjectId(string $objectId): static {
        $this->objectId = $objectId;

        return $this;
    }

    /**
     * @deprecated
     * @return array<string,scalar>
     */
    public function getArguments(): array {
        return $this->arguments;
    }

    public function uniqueId(): string {
        return json_encode([
            'objectId'  => $this->objectId,
            'arguments' => $this->arguments,
        ]);
    }
}
