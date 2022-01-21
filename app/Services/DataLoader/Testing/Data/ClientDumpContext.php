<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Schema\CompanyKpis;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\DocumentEntry;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use Faker\Generator;

use function array_fill_keys;
use function array_filter;
use function array_unique;
use function array_values;
use function fclose;
use function fopen;
use function fputcsv;
use function is_array;

use const SORT_REGULAR;

class ClientDumpContext {
    public const DISTRIBUTORS = 'distributors';
    public const RESELLERS    = 'resellers';
    public const CUSTOMERS    = 'customers';
    public const TYPES        = 'types';
    public const OEMS         = 'oems';

    public function __construct(
        protected Generator $faker,
        protected Normalizer $normalizer,
    ) {
        // empty
    }

    /**
     * @param array<string> $data
     *
     * @return array<mixed>
     */
    public function get(string $path, array $data = null): array {
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
            } elseif ($object instanceof CompanyKpis) {
                $resellers[] = $object->resellerId ?? null;
            } else {
                // empty
            }
        }

        // Cleanup
        $data    = array_fill_keys((array) $data, true);
        $context = [
            static::DISTRIBUTORS => static fn() => $distributors,
            static::RESELLERS    => static fn() => $resellers,
            static::CUSTOMERS    => static fn() => $customers,
            static::OEMS         => fn() => $this->getOemsCsv($oems, $path),
            static::TYPES        => static fn() => $types,
        ];

        foreach ($context as $key => $value) {
            if ($data && !isset($data[$key])) {
                unset($context[$key]);

                continue;
            }

            $value = $value();

            if (is_array($value)) {
                $value = array_values(array_filter(array_unique($value, SORT_REGULAR)));
            }

            $context[$key] = $value;
        }

        // Return
        return array_filter($context);
    }

    /**
     * @param array{?string,?string,?string} $oems
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
                        $this->faker->sentence,
                        $level,
                        $this->faker->sentence,
                        $this->faker->text,
                    ]);
                }
            }

            fclose($csv);
        }

        return $file;
    }
}
