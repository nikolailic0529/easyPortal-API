<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Models\Logs\AnalyzeAsset;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Client\GraphQL\GraphQL;
use App\Services\DataLoader\Resolver\Resolvers\AnalyzeAssetResolver;
use App\Services\DataLoader\Resolver\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\Types\Company;
use App\Services\DataLoader\Schema\Types\CompanyType;
use App\Services\DataLoader\Schema\Types\ViewAsset;
use App\Services\DataLoader\Schema\Types\ViewCompany;
use App\Utils\Console\WithOptions;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Iterators\Contracts\ObjectIterator;
use Closure;
use Config\Constants;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Symfony\Component\Console\Attribute\AsCommand;

use function array_filter;
use function array_map;
use function array_unique;
use function end;
use function implode;
use function is_null;
use function reset;
use function str_pad;

use const STR_PAD_LEFT;

#[AsCommand(name: 'ep:data-loader-assets-analyze')]
class AssetsAnalyze extends Command {
    use WithOptions;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:data-loader-assets-analyze
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
        Client $client,
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
        $lastId = null;
        $chunk  = $this->getIntOption('chunk')
            ?: ($config->get('ep.data_loader.chunk') ?? Constants::EP_DATA_LOADER_CHUNK);

        if ($this->getBoolOption('continue')) {
            $lastId = AnalyzeAsset::query()->orderByDesc('created_at')->first()?->getKey();
        }

        if ($lastId) {
            $this->info("Continued from #{$lastId}.");
            $this->newLine();
        }

        // Process
        GlobalScopes::callWithoutAll(function () use (
            $lastId,
            $chunk,
            $client,
            $assetResolver,
            $resellerResolver,
            $customerResolver,
            $analyzeResolver,
        ): void {
            $this->process(
                $this->getIterator($client, $lastId, $chunk),
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

    /**
     * @param ObjectIterator<ViewAsset> $iterator
     */
    protected function process(
        ObjectIterator $iterator,
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

        foreach ($iterator->onBeforeChunk($each) as $item) {
            /** @var ViewAsset $item */
            $id      = $item->id;
            $analyze = $analyzeResolver->get($id);

            if (!$analyze) {
                $analyze     = new AnalyzeAsset();
                $analyze->id = $id;
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

    protected function getCompanyType(Company|ViewCompany|null $company): string {
        $type = '';

        if ($company instanceof ViewCompany) {
            $type = implode(',', array_filter(array_map(static function (CompanyType $type): string {
                return $type->type;
            }, $company->companyTypes ?? [])));
        } elseif ($company instanceof Company) {
            $type = (string) $company->companyType;
        } else {
            // else
        }

        return $type;
    }

    /**
     * @return ObjectIterator<ViewAsset>
     */
    protected function getIterator(Client $client, ?string $lastId, int $chunk): ObjectIterator {
        return $client
            ->getLastIdBasedIterator(
                new class() extends GraphQL {
                    public function getSelector(): string {
                        return 'getAssets';
                    }

                    public function __toString(): string {
                        return /** @lang GraphQL */ <<<'GRAPHQL'
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
                        GRAPHQL;
                    }
                },
                [],
                static function (array $data): ViewAsset {
                    return new ViewAsset($data);
                },
            )
            ->setOffset($lastId)
            ->setChunkSize($chunk);
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
            $assetResolver->reset()->prefetch(
                array_filter(array_unique(array_map(static function (ViewAsset $asset): string {
                    return $asset->id;
                }, $assets))),
            );

            $analyzeResolver->reset()->prefetch(
                array_filter(array_unique(array_map(static function (ViewAsset $asset): string {
                    return $asset->id;
                }, $assets))),
            );

            $resellerResolver->reset()->prefetch(
                array_filter(array_unique(array_map(static function (ViewAsset $asset): ?string {
                    return $asset->reseller->id ?? null;
                }, $assets))),
            );

            $customerResolver->reset()->prefetch(
                array_filter(array_unique(array_map(static function (ViewAsset $asset): ?string {
                    return $asset->customer->id ?? null;
                }, $assets))),
            );
        };
    }
}
