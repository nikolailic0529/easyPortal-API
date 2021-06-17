<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Loader;
use Illuminate\Console\Command;
use Psr\Log\LoggerInterface;
use Throwable;

abstract class Update extends Command {
    /**
     * @param array<string> $ids
     */
    protected function process(LoggerInterface $logger, Loader $loader, array $ids, bool $create = false): int {
        $result = static::SUCCESS;

        foreach ($ids as $id) {
            $this->output->write("{$id} ... ");

            try {
                $model = $create ? $loader->create($id) : $loader->update($id);

                if ($model) {
                    $this->info('OK');
                } else {
                    $this->warn('not found in cosmos');
                }
            } catch (Throwable $exception) {
                $this->warn($exception->getMessage());
                $logger->warning(__METHOD__, [
                    'id'        => $id,
                    'exception' => $exception,
                ]);

                $result = static::FAILURE;
            }
        }

        $this->newLine();
        $this->info('Done.');

        return $result;
    }
}
