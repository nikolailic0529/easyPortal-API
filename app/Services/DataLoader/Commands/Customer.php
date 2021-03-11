<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Commands\Concerns\WithBooleanOptions;
use App\Services\DataLoader\DataLoaderService;
use Illuminate\Console\Command;
use Psr\Log\LoggerInterface;
use Throwable;

use function array_unique;
use function count;

class Customer extends Command {
    use WithBooleanOptions;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'data-loader:customer
        {id* : The ID of the company}
        {--l|locations : Load locations (default)}
        {--L|no-locations : Skip locations}
        {--c|contacts : Load contacts (default)}
        {--C|no-contacts : Skip contacts}
        {--a|assets : Load assets}
        {--A|no-assets : Skip assets (default)}
    ';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Load the customer(s) with given ID(s).';

    public function handle(DataLoaderService $service, LoggerInterface $logger): int {
        $result = static::SUCCESS;
        $loader = $service->getCustomerLoader();
        $ids    = array_unique($this->argument('id'));
        $bar    = $this->output->createProgressBar(count($ids));

        $loader->setWithLocations($this->getBooleanOption('locations', true));
        $loader->setWithContacts($this->getBooleanOption('contacts', true));
        $loader->setWithAssets($this->getBooleanOption('assets', false));

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
