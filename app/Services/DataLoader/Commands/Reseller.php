<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Commands\Concerns\WithBooleanOptions;
use App\Services\DataLoader\DataLoaderService;
use Illuminate\Console\Command;
use Psr\Log\LoggerInterface;
use Throwable;

use function array_unique;
use function count;

class Reseller extends Command {
    use WithBooleanOptions;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:data-loader-reseller
        {id* : The ID of the company}
        {--l|locations : Load locations (default)}
        {--L|no-locations : Skip locations}
        {--a|assets : Load assets}
        {--A|no-assets : Skip assets (default)}
        {--ad|assets-documents : Load assets documents (and warranties), required --a|assets (default)}
        {--AD|no-assets-documents : Skip assets documents}
    ';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Load the reseller(s) with given ID(s).';

    public function handle(DataLoaderService $service, LoggerInterface $logger): int {
        $result = static::SUCCESS;
        $loader = $service->getResellerLoader();
        $ids    = array_unique($this->argument('id'));
        $bar    = $this->output->createProgressBar(count($ids));

        $loader->setWithLocations($this->getBooleanOption('locations', true));
        $loader->setWithAssets($this->getBooleanOption('assets', false));
        $loader->setWithAssetsDocuments($this->getBooleanOption('assets-documents', true));

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
