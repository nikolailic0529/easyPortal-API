<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\CustomerLoaderFinder;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Finders\DistributorLoaderFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Finders\ResellerLoaderFinder;
use App\Services\DataLoader\Loader;
use Illuminate\Console\Command;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Throwable;

abstract class Update extends Command {
    abstract protected function makeLoader(Container $container): Loader;

    /**
     * @param array<string> $ids
     */
    protected function process(
        ExceptionHandler $handler,
        Container $container,
        array $ids,
        bool $create = false,
    ): int {
        if ($create) {
            $container->bind(DistributorFinder::class, DistributorLoaderFinder::class);
            $container->bind(ResellerFinder::class, ResellerLoaderFinder::class);
            $container->bind(CustomerFinder::class, CustomerLoaderFinder::class);
        }

        $result = static::SUCCESS;
        $loader = $this->makeLoader($container);

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
                $handler->report($exception);

                $result = static::FAILURE;
            }
        }

        $this->newLine();
        $this->info('Done.');

        return $result;
    }
}
