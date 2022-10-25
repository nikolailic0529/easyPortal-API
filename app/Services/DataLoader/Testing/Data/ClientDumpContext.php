<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Schema\CompanyKpis;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\DocumentEntry;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use Faker\Generator;

use function array_filter;
use function array_unique;
use function array_values;
use function fclose;
use function fopen;
use function fputcsv;

use const SORT_REGULAR;

class ClientDumpContext {
    public function __construct(
        protected Generator $faker,
        protected Normalizer $normalizer,
    ) {
        // empty
    }

    /**
     * @return array<string, array<string>>
     */
    public function get(string $path): array {
        // Extract
        $distributors = [];
        $resellers    = [];
        $customers    = [];
        $assets       = [];
        $types        = [];
        $oems         = [];
        $oem          = null;

        foreach ((new ClientDumpsIterator($path))->getResponseIterator() as $object) {
            if ($object instanceof ViewAsset) {
                $resellers[] = $object->resellerId ?? null;
                $resellers[] = $object->reseller->id ?? null;
                $customers[] = $object->customerId ?? null;
                $customers[] = $object->customer->id ?? null;
                $assets[]    = $object->id ?? null;
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
                    $object->serviceGroupSku ?? null,
                    $object->serviceLevelSku ?? null,
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
                    $object->serviceGroupSku ?? null,
                    $object->serviceLevelSku ?? null,
                ];
            } elseif ($object instanceof CompanyKpis) {
                $resellers[] = $object->resellerId ?? null;
            } else {
                // empty
            }
        }

        // Cleanup
        $context = [
            Context::DISTRIBUTORS => $distributors,
            Context::RESELLERS    => $resellers,
            Context::CUSTOMERS    => $customers,
            Context::ASSETS       => $assets,
            Context::TYPES        => $types,
            Context::OEMS         => [
                $this->getOemsCsv($oems, $path),
            ],
        ];

        foreach ($context as $key => $value) {
            $context[$key] = array_values(array_filter(array_unique($value, SORT_REGULAR)));
        }

        $context = array_filter($context);

        // Return
        return $context;
    }

    /**
     * @param array<int, array{?string,?string,?string}> $oems
     */
    protected function getOemsCsv(array $oems, string $path): ?string {
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
                        $this->faker->sentence(),
                        $level,
                        $this->faker->sentence(),
                        $this->faker->text(),
                    ]);
                }
            }

            fclose($csv);
        }

        return $file;
    }
}
