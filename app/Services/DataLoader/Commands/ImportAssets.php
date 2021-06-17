<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Container\Container as DataLoaderContainer;
use App\Services\DataLoader\Loaders\AssetLoader;
use App\Services\DataLoader\Schema\Type;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Psr\Log\LoggerInterface;
use Throwable;

use function end;
use function reset;
use function str_pad;

use const STR_PAD_LEFT;

class ImportAssets extends Command {
    use GlobalScopes;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:data-loader-import-assets
        {--continue= : }
        {--limit= : }
        {--chunk= : chunk size}';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Import all assets.';

    public function handle(
        LoggerInterface $logger,
        Container $container,
        Client $client,
    ): int {
        // Process
        $this->callWithoutGlobalScopes(
            [OwnedByOrganizationScope::class],
            function () use ($logger, $container, $client): void {
                $this->process($logger, $container, $client);
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
        Client $client,
    ): void {
        /** @var array{index:int,first:string,last:string,failed:int} $previous */
        $previous  = null;
        $processed = 0;
        $failed    = 0;
        $loader    = $container->make(DataLoaderContainer::class)->make(AssetLoader::class);
        $iterator  = $client->getAssetsWithDocuments();
        $each      = function (array $assets) use ($container, &$loader, &$previous, &$processed, &$failed): void {
            // Reset loader
            if ($previous) {
                $loader = $container->make(DataLoaderContainer::class)->make(AssetLoader::class);
            }

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
        $this->info(
            'Chunk #         : From                                 ... To                                   -   Failed in chunk      Processed /     Failed',
        );
        // @phpcs:enable

        foreach ($iterator->each($each) as $asset) {
            /** @var \App\Services\DataLoader\Schema\ViewAsset $asset */
            try {
                $loader->create($asset);
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
}
