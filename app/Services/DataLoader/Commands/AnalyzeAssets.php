<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Models\Logs\AnalyzeAsset;
use App\Services\DataLoader\Client\LastIdBasedIterator;
use App\Services\DataLoader\DataLoaderService;
use App\Services\DataLoader\Resolvers\AnalyzeAssetResolver;
use App\Services\DataLoader\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\CompanyType;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;

use function array_filter;
use function array_map;
use function array_unique;
use function end;
use function implode;
use function is_null;
use function reset;
use function str_pad;

use const STR_PAD_LEFT;

class AnalyzeAssets extends Command {
    use GlobalScopes;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:data-loader-analyze-assets
        {--continue : continue from last analyzed asset}
        {--reset : reset all existing statuses}
        {--chunk= : chunk size}';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Analyze assets to find assets without reseller/customer or missed in database.';

    public function handle(
        Repository $config,
        DataLoaderService $service,
        AssetResolver $assetResolver,
        ResellerResolver $resellerResolver,
        CustomerResolver $customerResolver,
        AnalyzeAssetResolver $analyzeResolver,
    ): int {
        // Reset?
        if ($this->option('reset') && $this->confirm('Are you sure to reset existing statuses?')) {
            $this->warn('Resetting all existing statuses...');

            AnalyzeAsset::query()->delete();

            $this->info('Done.');
            $this->newLine();

            return self::SUCCESS;
        }

        // Continue?
        $chunk  = ((int) $this->option('chunk')) ?: $config->get('ep.data_loader.chunk');
        $lastId = null;

        if ($this->option('continue')) {
            $lastId = AnalyzeAsset::query()->orderByDesc('created_at')->first()?->getKey();
        }

        if ($lastId) {
            $this->info("Continued from #{$lastId}.");
            $this->newLine();
        }

        // Process
        $this->callWithoutGlobalScopes([OwnedByOrganizationScope::class], function () use (
            $lastId,
            $chunk,
            $service,
            $assetResolver,
            $resellerResolver,
            $customerResolver,
            $analyzeResolver,
        ): void {
            $this->process(
                $this->getIterator($service, $lastId, $chunk),
                $assetResolver,
                $resellerResolver,
                $customerResolver,
                $analyzeResolver,
            );
        });

        // Done
        $this->info('Done.');

        // Return
        return self::SUCCESS;
    }

    protected function process(
        LastIdBasedIterator $iterator,
        AssetResolver $assetResolver,
        ResellerResolver $resellerResolver,
        CustomerResolver $customerResolver,
        AnalyzeAssetResolver $analyzeResolver,
    ): void {
        /** @var array{index:int,first:string,last:string,invalid:int} $previous */
        $previous   = null;
        $analyzed   = 0;
        $invalid    = 0;
        $prefetcher = $this->getPrefetcher($assetResolver, $resellerResolver, $customerResolver, $analyzeResolver);
        $each       = function (array $assets) use ($prefetcher, &$previous, &$analyzed, &$invalid): void {
            // Dump
            if ($previous) {
                $this->dump($previous, $analyzed, $invalid);
            }

            // Update
            $previous = [
                'index'   => ($previous['index'] ?? 0) + 1,
                'first'   => reset($assets),
                'last'    => end($assets),
                'invalid' => $invalid,
            ];

            // Prefetch
            $prefetcher($assets);
        };

        // @phpcs:disable Generic.Files.LineLength.TooLong
        $this->info(
            'Chunk #         : From                                 ... To                                   -   Invalid in chunk      Analyzed /    Invalid',
        );
        // @phpcs:enable

        foreach ($iterator->each($each) as $item) {
            /** @var \App\Services\DataLoader\Schema\ViewAsset $item */
            $id      = $item->id;
            $analyze = $analyzeResolver->get($id);

            if (!$analyze) {
                $analyze                           = new AnalyzeAsset();
                $analyze->{$analyze->getKeyName()} = $id;
            }

            // Asset
            $asset            = $assetResolver->get($id);
            $analyze->unknown = is_null($asset) ? true : null;

            // Reseller
            $resellerId = $item->reseller->id ?? null;

            if ($resellerId) {
                $types    = $this->getCompanyType($item->reseller ?? null);
                $reseller = $resellerResolver->get($resellerId);

                $analyze->reseller_null    = null;
                $analyze->reseller_types   = $types !== 'RESELLER' ? $types : null;
                $analyze->reseller_unknown = is_null($reseller) ? $resellerId : null;
            } else {
                $analyze->reseller_null    = true;
                $analyze->reseller_types   = null;
                $analyze->reseller_unknown = null;
            }

            // Customer
            $customerId = $item->customer->id ?? null;

            if ($customerId) {
                $types    = $this->getCompanyType($item->customer ?? null);
                $customer = $customerResolver->get($customerId);

                $analyze->customer_null    = null;
                $analyze->customer_types   = $types !== 'CUSTOMER' ? $types : null;
                $analyze->customer_unknown = is_null($customer) ? $customerId : null;
            } else {
                $analyze->customer_null    = true;
                $analyze->customer_types   = null;
                $analyze->customer_unknown = null;
            }

            // Save
            if ($this->isValid($analyze)) {
                $analyze->delete();
            } else {
                $analyze->save();
                $invalid++;
            }

            // Count
            $analyzed++;
        }

        // Last chunk
        $this->dump($previous, $analyzed, $invalid);

        // Finalizing
        $this->newLine();

        $message = "Analyzed: {$analyzed}, Invalid: {$invalid}";

        if ($invalid) {
            $this->warn($message);
        } else {
            $this->info($message);
        }

        $this->newLine();
    }

    /**
     * @param array{index:int,first:string,last:string,invalid:int} $chunk
     */
    protected function dump(array $chunk, int $assetsAnalyzed, int $assetsInvalid): void {
        $length       = 10;
        $analyzed     = str_pad((string) $assetsAnalyzed, $length, ' ', STR_PAD_LEFT);
        $invalid      = str_pad((string) $assetsInvalid, $length, ' ', STR_PAD_LEFT);
        $chunkInvalid = str_pad((string) ($assetsInvalid - $chunk['invalid']), $length, ' ', STR_PAD_LEFT);
        $index        = str_pad((string) $chunk['index'], $length, ' ', STR_PAD_LEFT);
        $first        = str_pad($chunk['first'] instanceof Type ? $chunk['first']->id : 'null', 36, ' ', STR_PAD_LEFT);
        $last         = str_pad($chunk['last'] instanceof Type ? $chunk['last']->id : 'null', 36, ' ', STR_PAD_LEFT);
        $message      = "Chunk {$index}: {$first} ... {$last} - {$chunkInvalid} invalid   ({$analyzed} / {$invalid})";

        if ($assetsInvalid - $chunk['invalid'] > 0) {
            $this->warn($message);
        } else {
            $this->info($message);
        }
    }

    protected function isValid(AnalyzeAsset $analyze): bool {
        return $analyze->unknown === null
            && $analyze->reseller_null === null
            && $analyze->reseller_types === null
            && $analyze->reseller_unknown === null
            && $analyze->customer_null === null
            && $analyze->customer_types === null
            && $analyze->customer_unknown === null;
    }

    protected function getCompanyType(?Company $company): string {
        return implode(',', array_filter(array_map(static function (CompanyType $type): string {
            return $type->type;
        }, $company->companyTypes ?? [])));
    }

    protected function getIterator(DataLoaderService $service, ?string $lastId, int $chunk): LastIdBasedIterator {
        return $service->getClient()
            ->getLastIdBasedIterator(
                'getAssets',
                /** @lang GraphQL */ <<<'GRAPHQL'
                query items($limit: Int, $lastId: String) {
                    getAssets(limit: $limit, lastId: $lastId) {
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
                static function (array $data): ViewAsset {
                    return new ViewAsset($data);
                },
            )
            ->lastId($lastId)
            ->chunk($chunk);
    }

    protected function getPrefetcher(
        AssetResolver $assetResolver,
        ResellerResolver $resellerResolver,
        CustomerResolver $customerResolver,
        AnalyzeAssetResolver $analyzeResolver,
    ): Closure {
        return static function (array $assets) use (
            $assetResolver,
            $resellerResolver,
            $customerResolver,
            $analyzeResolver,
        ): void {
            // Prefetch
            $assetResolver->prefetch(
                array_filter(array_unique(array_map(static function (ViewAsset $asset): string {
                    return $asset->id;
                }, $assets))),
                true,
            );

            $analyzeResolver->prefetch(
                array_filter(array_unique(array_map(static function (ViewAsset $asset): string {
                    return $asset->id;
                }, $assets))),
                true,
            );

            $resellerResolver->prefetch(
                array_filter(array_unique(array_map(static function (ViewAsset $asset): ?string {
                    return $asset->reseller->id ?? null;
                }, $assets))),
                true,
            );

            $customerResolver->prefetch(
                array_filter(array_unique(array_map(static function (ViewAsset $asset): ?string {
                    return $asset->customer->id ?? null;
                }, $assets))),
                true,
            );
        };
    }
}
