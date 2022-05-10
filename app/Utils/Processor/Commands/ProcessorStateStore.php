<?php declare(strict_types = 1);

namespace App\Utils\Processor\Commands;

use App\Services\Service;
use App\Utils\Processor\ServiceStore;
use Illuminate\Console\Command;

class ProcessorStateStore extends ServiceStore {
    public function __construct(
        Service $service,
        Command $command,
        protected string $uuid,
    ) {
        parent::__construct($service, [$command, $this->getUuid()]);
    }

    public function getUuid(): string {
        return $this->uuid;
    }
}
