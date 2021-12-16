<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use App\Services\DataLoader\Finders\OemFinder;
use App\Services\DataLoader\Finders\ServiceGroupFinder;
use App\Services\DataLoader\Finders\ServiceLevelFinder;
use App\Services\DataLoader\Schema\CompanyKpis;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\DocumentEntry;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use App\Services\DataLoader\Testing\Finders\OemFinder as OemFinderImpl;
use App\Services\DataLoader\Testing\Finders\ServiceGroupFinder as ServiceGroupFinderImpl;
use App\Services\DataLoader\Testing\Finders\ServiceLevelFinder as ServiceLevelFinderImpl;

use function array_filter;
use function array_unique;
use function array_values;
use function fclose;
use function fopen;
use function fputcsv;
use function is_array;

use const SORT_REGULAR;

abstract class AssetsData extends Data {
    /**
     * @inerhitDoc
     */
    protected function generateBindings(): array {
        return [
            OemFinder::class          => OemFinderImpl::class,
            ServiceGroupFinder::class => ServiceGroupFinderImpl::class,
            ServiceLevelFinder::class => ServiceLevelFinderImpl::class,
        ];
    }

    /**
     * @return array<mixed>
     */
    protected function generateContext(string $path): array {
        // Extract
        $distributors = [];
        $resellers    = [];
        $customers    = [];
        $types        = [];
        $oems         = [];
        $oem          = null;

        foreach ((new ClientDumpsIterator($path))->getResponseIterator() as $object) {
            if ($object instanceof ViewAsset) {
                $resellers[] = $object->resellerId ?? null;
                $resellers[] = $object->reseller->id ?? null;
                $customers[] = $object->customerId ?? null;
                $customers[] = $object->customer->id ?? null;
                $oem         = $object->vendor ?? null;
                $oems[]      = [
                    $oem,
                    null,
                    null,
                ];
            } elseif ($object instanceof ViewAssetDocument) {
                $resellers[] = $object->reseller->id ?? null;
                $customers[] = $object->customer->id ?? null;
                $types[]     = $object->document->type ?? null;
                $oems[]      = [
                    $object->document->vendorSpecificFields->vendor ?? $oem ?? null,
                    $object->supportPackage ?? null,
                    $object->skuNumber ?? null,
                ];
            } elseif ($object instanceof ViewDocument) {
                $distributors[] = $object->distributorId ?? null;
                $resellers[]    = $object->resellerId ?? null;
                $customers[]    = $object->customerId ?? null;
                $types[]        = $object->type ?? null;
            } elseif ($object instanceof Document) {
                $distributors[] = $object->distributorId ?? null;
                $resellers[]    = $object->resellerId ?? null;
                $customers[]    = $object->customerId ?? null;
                $types[]        = $object->type ?? null;
                $oem            = $object->vendorSpecificFields->vendor ?? null;
                $oems[]         = [
                    $oem,
                    null,
                    null,
                ];
            } elseif ($object instanceof DocumentEntry) {
                $oems[] = [
                    $oem,
                    $object->supportPackage ?? null,
                    $object->skuNumber ?? null,
                ];
            } else {
                // empty
            }
        }

        // Store OEMs
        $file = null;
        $oems = array_values(array_filter(array_unique($oems, SORT_REGULAR), static function (array $oem): bool {
            return $oem && array_unique($oem);
        }));

        if ($oems) {
            $file = 'oem.csv';
            $csv  = fopen("{$path}/{$file}", 'w');

            fputcsv($csv, [
                'Vendor',
                'Service Group SKU',
                'Service Group Description',
                'Service Level SKU',
                'English',
                'English',
            ]);

            foreach ($oems as $oem) {
                [$oem, $group, $level] = $oem;

                $oem   = $this->normalizer->string($oem);
                $group = $this->normalizer->string($group) ?? $oem;
                $level = $this->normalizer->string($level) ?? $oem;

                if ($oem && $group && $level) {
                    fputcsv($csv, [
                        $oem,
                        $group,
                        $this->faker->sentence,
                        $level,
                        $this->faker->sentence,
                        $this->faker->text,
                    ]);
                }
            }

            fclose($csv);
        }

        // Cleanup
        $context = [
            static::CONTEXT_DISTRIBUTORS => $distributors,
            static::CONTEXT_RESELLERS    => $resellers,
            static::CONTEXT_CUSTOMERS    => $customers,
            static::CONTEXT_OEMS         => $file,
            static::CONTEXT_TYPES        => $types,
        ];

        foreach ($context as &$data) {
            if (is_array($data)) {
                $data = array_values(array_filter(array_unique($data, SORT_REGULAR)));
            }
        }

        // Return
        return array_filter($context);
    }

    abstract protected function generateData(string $path): bool;
}
