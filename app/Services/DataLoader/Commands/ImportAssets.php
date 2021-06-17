<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Client\QueryIterator;
use App\Services\DataLoader\Commands\Concerns\WithBooleanOptions;
use App\Services\DataLoader\DataLoaderService;
use App\Services\DataLoader\Factories\AssetFactory;
use App\Services\DataLoader\Factories\CustomerFactory;
use App\Services\DataLoader\Factories\ResellerFactory;
use App\Services\DataLoader\Loaders\Concerns\CalculatedProperties;
use App\Services\DataLoader\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use Throwable;

use function end;
use function reset;
use function str_pad;

use const STR_PAD_LEFT;

class ImportAssets extends Command {
    use GlobalScopes;
    use WithBooleanOptions;
    use CalculatedProperties;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:data-loader-import-assets
        {--u|update : Update asset if exists}
        {--U|no-update : Do not update asset if exists (default)}
        {--from= : start processing from given asset}
        {--limit= : max assets to process}
        {--chunk= : chunk size}
    ';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Import all assets.';

    public function handle(
        LoggerInterface $logger,
        Container $container,
        Repository $config,
        Client $client,
    ): int {
        // Settings
        $iterator = $client->getAssetsWithDocuments();
        $update   = $this->getBooleanOption('update', false);
        $chunk    = ((int) $this->option('chunk')) ?: $config->get('ep.data_loader.chunk');
        $limit    = (int) $this->option('limit');
        $from     = $this->option('from');

        if ($chunk) {
            $this->output->write("Chunk: {$chunk}; ");
            $iterator->chunk($chunk);
        }

        if ($limit) {
            $this->output->write("Limit: {$limit}; ");
            $iterator->limit($limit);
        }

        if ($from) {
            $this->output->write("From: #{$from}; ");
            $iterator->lastId($from);
        }

        if ($chunk || $limit || $from) {
            $this->newLine(2);
        }

        // Process
        $this->callWithoutGlobalScopes(
            [OwnedByOrganizationScope::class],
            function () use ($logger, $container, $iterator, $update): void {
                $this->process($logger, $container, $iterator, $update);
            },
        );

        // Done
        $this->info('Done.');

        // Return
        return self::SUCCESS;
    }

    protected function process(
        LoggerInterface $logger,
        Container $container,
        QueryIterator $iterator,
        bool $update,
    ): void {
        /** @var array{index:int,first:string,last:string,failed:int} $previous */
        $previous  = null;
        $processed = 0;
        $failed    = 0;
        $service   = $container->make(DataLoaderService::class);
        $resolver  = $service->getContainer()->make(AssetResolver::class);
        $loader    = $service->getAssetLoader();
        $each      = function (
            array $assets,
        ) use (
            $logger,
            $container,
            &$service,
            &$loader,
            &$resolver,
            &$previous,
            &$processed,
            &$failed,
        ): void {
            // Update calculated
            if ($previous) {
                $this->updateCalculatedProperties($logger, $service);
            }

            // Reset loader & Prefetch
            if ($previous) {
                $service  = $container->make(DataLoaderService::class);
                $loader   = $service->getAssetLoader();
                $resolver = $service->getContainer()->make(AssetResolver::class);
            }

            $service->getContainer()
                ->make(AssetFactory::class)
                ->prefetch($assets, true, static function (EloquentCollection $assets): void {
                    $assets->loadMissing('documentEntries');
                    $assets->loadMissing('warranties');
                    $assets->loadMissing('warranties.services');
                    $assets->loadMissing('contacts');
                    $assets->loadMissing('tags');
                });

            // Dump
            if ($previous) {
                $this->dump($previous, $processed, $failed);
            }

            // Update
            $previous = [
                'index'  => ($previous['index'] ?? 0) + 1,
                'first'  => reset($assets),
                'last'   => end($assets),
                'failed' => $failed,
            ];
        };

        // @phpcs:disable Generic.Files.LineLength.TooLong
        $this->line(
            'Chunk #         : From                                 ... To                                   -   Failed in chunk      Processed /     Failed',
        );
        // @phpcs:enable

        foreach ($iterator->each($each) as $asset) {
            /** @var \App\Services\DataLoader\Schema\ViewAsset $asset */
            try {
                if ($update || !$resolver->get($asset->id)) {
                    $loader->create($asset);
                }
            } catch (Throwable $exception) {
                $failed++;

                $logger->warning('Failed to import asset.', [
                    'asset'     => $asset,
                    'exception' => $exception,
                ]);
            }

            // Count
            $processed++;
        }

        // Last chunk
        $this->updateCalculatedProperties($logger, $service);
        $this->dump($previous, $processed, $failed);

        // Finalizing
        $this->newLine();

        $message = "Processed: {$processed}, Failed: {$failed}";

        if ($failed) {
            $this->warn($message);
        } else {
            $this->info($message);
        }

        $this->newLine();
    }

    /**
     * @param array{index:int,first:string,last:string,failed:int} $chunk
     */
    protected function dump(array $chunk, int $assetsProcessed, int $assetsFailed): void {
        $length      = 10;
        $processed   = str_pad((string) $assetsProcessed, $length, ' ', STR_PAD_LEFT);
        $failed      = str_pad((string) $assetsFailed, $length, ' ', STR_PAD_LEFT);
        $chunkFailed = str_pad((string) ($assetsFailed - $chunk['failed']), $length, ' ', STR_PAD_LEFT);
        $index       = str_pad((string) $chunk['index'], $length, ' ', STR_PAD_LEFT);
        $first       = str_pad($chunk['first'] instanceof Type ? $chunk['first']->id : 'null', 36, ' ', STR_PAD_LEFT);
        $last        = str_pad($chunk['last'] instanceof Type ? $chunk['last']->id : 'null', 36, ' ', STR_PAD_LEFT);
        $message     = "Chunk {$index}: {$first} ... {$last} - {$chunkFailed} failed    ({$processed} / {$failed})";

        if ($assetsFailed - $chunk['failed'] > 0) {
            $this->warn($message);
        } else {
            $this->info($message);
        }
    }

    protected function updateResellersCalculatedProperties(LoggerInterface $logger, Collection $resellers): void {
        foreach ($resellers as $reseller) {
            try {
                $this->updateResellerCalculatedProperties($reseller);
            } catch (Throwable $exception) {
                $logger->warning(__METHOD__, [
                    'reseller'  => $reseller,
                    'exception' => $exception,
                ]);
            }
        }
    }

    protected function updateCustomersCalculatedProperties(LoggerInterface $logger, Collection $customers): void {
        foreach ($customers as $customer) {
            try {
                $this->updateCustomerCalculatedProperties($customer);
            } catch (Throwable $exception) {
                $logger->warning(__METHOD__, [
                    'customer'  => $customer,
                    'exception' => $exception,
                ]);
            }
        }
    }

    protected function updateCalculatedProperties(LoggerInterface $logger, mixed $service): void {
        $resellers = $service->getContainer()->make(ResellerResolver::class)->getResolved();
        $customers = $service->getContainer()->make(CustomerResolver::class)->getResolved();

        $this->updateResellersCalculatedProperties($logger, $resellers);
        $this->updateCustomersCalculatedProperties($logger, $customers);
    }
}
