<?php declare(strict_types = 1);

namespace App\Utils\Console;

use Illuminate\Console\Command;

trait CommandResult {
    protected function isCommandSuccessful(int $result): bool {
        return $result === Command::SUCCESS;
    }

    protected function checkCommandResult(int $result): int {
        if (!$this->isCommandSuccessful($result)) {
            throw new CommandFailed($this, $result);
        }

        return $result;
    }
}
