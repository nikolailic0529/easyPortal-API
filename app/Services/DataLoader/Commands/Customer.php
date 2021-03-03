<?php

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\DataLoaderService;
use App\Services\DataLoader\Loaders\CustomerLoader;
use Exception;
use Illuminate\Console\Command;
use Psr\Log\LoggerInterface;

use Throwable;

use function array_unique;
use function count;

class Customer extends Command {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'data-loader:customer {id* : The ID of the company}';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Load the customer(s) with given ID(s).';

    public function handle(DataLoaderService $service, LoggerInterface $logger): int {
        $result = static::SUCCESS;
        $loader = $service->make(CustomerLoader::class);
        $ids    = array_unique($this->argument('id'));
        $bar    = $this->output->createProgressBar(count($ids));

        $bar->start();

        foreach ($ids as $id) {
            try {
                if (!$loader->load($id)) {
                    $this->warn(" Not found #{$id}");
                }
            } catch (Throwable $exception) {
                $this->warn(" Failed #{$id}: {$exception->getMessage()}");

                $logger->warning(__METHOD__, [
                    'id'        => $id,
                    'exception' => $exception,
                ]);

                $result = static::FAILURE;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $result;
    }
}
