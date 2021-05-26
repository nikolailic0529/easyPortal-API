<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Exceptions\Contextable;
use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Services\DataLoader\Commands\Concerns\WithBooleanOptions;
use App\Services\DataLoader\DataLoaderService;
use App\Services\DataLoader\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\CompanyType;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;

use function array_filter;
use function array_map;
use function array_unique;
use function count;

class AnalyzeAssets extends Command {
    use WithBooleanOptions;
    use GlobalScopes;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:data-loader-analyze-assets
        {--chunk= : chunk size}
        {--offset=0 : initial offset}';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Analyze assets to find assets without reseller/customer or missed in database.';

    public function handle(
        LogManager $log,
        Repository $config,
        DataLoaderService $service,
        AssetResolver $assetResolver,
        ResellerResolver $resellerResolver,
        CustomerResolver $customerResolver,
    ): int {
        $logger = $log->channel(self::class);

        try {
            $this->callWithoutGlobalScopes([OwnedByOrganizationScope::class], function () use (
                $logger,
                $config,
                $service,
                $assetResolver,
                $resellerResolver,
                $customerResolver,
            ): void {
                $this->process($logger, $config, $service, $assetResolver, $resellerResolver, $customerResolver);
            });
        } catch (Exception $exception) {
            $logger->error('Failed', [
                'exception' => $exception,
                'context'   => $exception instanceof Contextable
                    ? $exception->context()
                    : null,
            ]);
        }

        return self::SUCCESS;
    }

    protected function process(
        LoggerInterface $logger,
        Repository $config,
        DataLoaderService $service,
        AssetResolver $assetResolver,
        ResellerResolver $resellerResolver,
        CustomerResolver $customerResolver,
    ): void {
        $processed = 0;
        $analyzed  = [];
        $client    = $service->getClient();
        $offset    = ((int) $this->option('offset')) ?: 0;
        $chunk     = ((int) $this->option('chunk')) ?: $config->get('ep.data_loader.chunk');
        $iterator  = $client
            ->iterator(
                'getAssets',
                /** @lang GraphQL */ <<<'GRAPHQL'
                query items($limit: Int, $offset: Int) {
                    getAssets(limit: $limit, offset: $offset) {
                        id
                        reseller {
                          id
                          companyTypes {
                            type
                          }
                        }
                        customer {
                          id
                          companyTypes {
                            type
                          }
                        }
                    }
                }
                GRAPHQL,
                [],
                static function (array $data): Asset {
                    return new Asset($data);
                },
            )
            ->offset($offset)
            ->chunk($chunk);
        $prefetch  = static function (array $assets) use (
            $logger,
            $processed,
            $assetResolver,
            $resellerResolver,
            $customerResolver,
        ): void {
            // Info
            $logger->info('Chunk loaded', [
                'processed' => $processed,
            ]);

            // Prefetch
            $assetResolver->prefetch(
                array_filter(array_unique(array_map(static function (Asset $asset): string {
                    return $asset->id;
                }, $assets))),
                true,
            );

            $resellerResolver->prefetch(
                array_filter(array_unique(array_map(static function (Asset $asset): ?string {
                    return $asset->reseller->id ?? null;
                }, $assets))),
                true,
            );

            $customerResolver->prefetch(
                array_filter(array_unique(array_map(static function (Asset $asset): ?string {
                    return $asset->customers->id ?? null;
                }, $assets))),
                true,
            );
        };

        $logger->info('Start', [
            'offset' => $offset,
            'chunk'  => $chunk,
        ]);

        foreach ($iterator->each($prefetch) as $asset) {
            /** @var \App\Services\DataLoader\Schema\Asset $asset */

            // Analize
            $id    = $asset->id;
            $entry = [
                'id'        => $id,
                'exists'    => (bool) $assetResolver->get($id),
                'duplicate' => isset($analyzed[$id]),
                'reseller'  => [
                    'id'     => $asset->reseller->id ?? null,
                    'types'  => $this->getCompanyTypes($asset->reseller),
                    'exists' => isset($asset->reseller->id) && $resellerResolver->get($asset->reseller->id),
                ],
                'customer'  => [
                    'id'     => $asset->customer->id ?? null,
                    'types'  => $this->getCompanyTypes($asset->customer),
                    'exists' => isset($asset->customer->id) && $customerResolver->get($asset->customer->id),
                ],
            ];

            $analyzed[$id] = true;

            // Valid?
            $valid = true
                && $entry['exists']
                && $entry['duplicate'] === false
                && $entry['reseller']['id']
                && $entry['reseller']['exists']
                && (count($entry['reseller']['types']) === 1 && $entry['reseller']['types'][0] === 'RESELLER')
                && $entry['customer']['id']
                && $entry['customer']['exists']
                && (count($entry['customer']['types']) === 1 && $entry['customer']['types'][0] === 'CUSTOMER');

            if ($valid) {
                $logger->debug('Ok', $entry);
            } else {
                $logger->info('Invalid', $entry);
            }

            // Count
            $processed++;
        }

        $logger->info('End', [
            'processed' => $processed,
        ]);
    }

    /**
     * @return array<string>
     */
    protected function getCompanyTypes(?Company $company): array {
        return array_filter(array_unique(array_map(static function (CompanyType $type): string {
            return $type->type;
        }, $company->companyTypes ?? [])));
    }
}
