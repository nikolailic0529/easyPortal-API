<?php declare(strict_types = 1);

namespace Tests\Data\DataLoader;

use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use App\Services\DataLoader\Testing\Data\ClientDumpsIterator;
use App\Services\DataLoader\Testing\Data\Data;
use Illuminate\Console\Command;

use function array_filter;
use function array_unique;
use function array_values;
use function fclose;
use function fopen;
use function fputcsv;
use function is_array;

use const SORT_REGULAR;

class AssetsImporterData extends Data {
    public const LIMIT                = 50;
    public const CHUNK                = 10;
    public const CONTEXT_DISTRIBUTORS = 'distributors';
    public const CONTEXT_RESELLERS    = 'resellers';
    public const CONTEXT_CUSTOMERS    = 'customers';
    public const CONTEXT_OEMS         = 'oems';

    /**
     * @inheritDoc
     */
    public function generate(string $path): array|bool {
        $result = $this->dumpClientResponses($path, function (): bool {
            $result  = $this->kernel->call('ep:data-loader-import-assets', [
                '--limit' => static::LIMIT,
                '--chunk' => static::CHUNK,
            ]);
            $success = $result === Command::SUCCESS;

            return $success;
        });

        if ($result) {
            $result = $this->getContext($path);
        }

        return $result;
    }

    /**
     * @return array<mixed>
     */
    protected function getContext(string $path): array {
        // Extract
        $distributors = [];
        $resellers    = [];
        $customers    = [];
        $oems         = [];

        foreach ((new ClientDumpsIterator($path))->getResponseIterator() as $object) {
            if ($object instanceof ViewAsset) {
                $resellers[] = $object->resellerId ?? null;
                $resellers[] = $object->reseller->id ?? null;
                $customers[] = $object->customerId ?? null;
                $customers[] = $object->customer->id ?? null;
            } elseif ($object instanceof ViewAssetDocument) {
                $resellers[] = $object->reseller->id ?? null;
                $customers[] = $object->customer->id ?? null;
                $oems[]      = [
                    $object->document->vendorSpecificFields->vendor ?? null,
                    $object->supportPackage ?? null,
                    $object->skuNumber ?? null,
                ];
            } elseif ($object instanceof ViewDocument) {
                $resellers[]    = $object->resellerId ?? null;
                $customers[]    = $object->customerId ?? null;
                $distributors[] = $object->distributorId ?? null;
            } else {
                // empty
            }
        }

        // Store OEMs
        $file = 'oem.csv';
        $oems = array_values(array_filter(array_unique($oems, SORT_REGULAR)));
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
            $group = $this->normalizer->string($group);
            $level = $this->normalizer->string($level);

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

        // Cleanup
        $context = [
            static::CONTEXT_DISTRIBUTORS => $distributors,
            static::CONTEXT_RESELLERS    => $resellers,
            static::CONTEXT_CUSTOMERS    => $customers,
            static::CONTEXT_OEMS         => $file,
        ];

        foreach ($context as &$data) {
            if (is_array($data)) {
                $data = array_values(array_filter(array_unique($data, SORT_REGULAR)));
            }
        }

        return $context;
    }
}
