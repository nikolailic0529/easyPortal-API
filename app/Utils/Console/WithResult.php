<?php declare(strict_types = 1);

namespace App\Utils\Console;

use Illuminate\Console\Command;

/**
 * @mixin Command
 */
trait WithResult {
    protected function result(bool $result): int {
        // Done
        if ($result) {
            $this->info('Done.');
        } else {
            $this->error('Failed.');
        }

        // Return
        return $result
            ? Command::SUCCESS
            : Command::FAILURE;
    }
}
