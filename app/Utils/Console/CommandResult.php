<?php declare(strict_types = 1);

namespace App\Utils\Console;

use Illuminate\Console\Command;

trait CommandResult {
    protected function checkCommandResult(int $result): int {
        if ($result !== Command::SUCCESS) {
            throw new CommandFailed($this, $result);
        }

        return $result;
    }
}
