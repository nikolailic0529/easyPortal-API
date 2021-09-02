<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use App\Services\DataLoader\Finders\OemFinder;
use App\Services\DataLoader\Finders\ServiceGroupFinder;
use App\Services\DataLoader\Finders\ServiceLevelFinder;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use App\Services\DataLoader\Testing\Finders\OemFinder as OemFinderImpl;
use App\Services\DataLoader\Testing\Finders\ServiceGroupFinder as ServiceGroupFinderImpl;
use App\Services\DataLoader\Testing\Finders\ServiceLevelFinder as ServiceLevelFinderImpl;
use Illuminate\Console\Command;

use function array_filter;
use function array_unique;
use function array_values;
use function fclose;
use function fopen;
use function fputcsv;
use function is_array;

use const SORT_REGULAR;

abstract class AssetsData extends Data {
    public const CONTEXT_DISTRIBUTORS = 'distributors';
    public const CONTEXT_RESELLERS    = 'resellers';
    public const CONTEXT_CUSTOMERS    = 'customers';
    public const CONTEXT_OEMS         = 'oems';

    /**
     * @inheritDoc
     */
    public function restore(string $path, array $context): bool {
        $result = true;

        if ($context[static::CONTEXT_OEMS]) {
            $result = $result && $this->kernel->call('ep:data-loader-import-oems', [
                    'file' => "{$path}/{$context[static::CONTEXT_OEMS]}",
                ]) === Command::SUCCESS;
        }

        if ($context[static::CONTEXT_DISTRIBUTORS]) {
            $result = $result && $this->kernel->call('ep:data-loader-update-distributor', [
                    'id'       => $context[static::CONTEXT_DISTRIBUTORS],
                    '--create' => true,
                ]) === Command::SUCCESS;
        }

        if ($context[static::CONTEXT_RESELLERS]) {
            $result = $result && $this->kernel->call('ep:data-loader-update-reseller', [
                    'id'       => $context[static::CONTEXT_RESELLERS],
                    '--create' => true,
                ]) === Command::SUCCESS;
        }

        if ($context[static::CONTEXT_CUSTOMERS]) {
            $result = $result && $this->kernel->call('ep:data-loader-update-customer', [
                    'id'       => $context[static::CONTEXT_CUSTOMERS],
                    '--create' => true,
                ]) === Command::SUCCESS;
        }

        return $result;
    }

    public function generate(string $path): array|bool {
        $result   = false;
        $bindings = [
            OemFinder::class          => OemFinderImpl::class,
            ServiceGroupFinder::class => ServiceGroupFinderImpl::class,
            ServiceLevelFinder::class => ServiceLevelFinderImpl::class,
        ];

        try {
            foreach ($bindings as $abstract => $concrete) {
                if (!$this->app->bound($abstract)) {
                    $this->app->bind($abstract, $concrete);
                } else {
                    unset($bindings[$abstract]);
                }
            }

            $result = $this->generateData($path);
        } finally {
            foreach ($bindings as $abstract => $concrete) {
                unset($this->app[$abstract]);
            }
        }

        if ($result) {
            $result = $this->generateContext($path);
        }

        return $result;
    }

    /**
     * @return array<mixed>
     */
    public function generateContext(string $path): array {
        // Extract
        $distributors = [];
        $resellers    = [];
        $customers    = [];
        $oems         = [];
        $oem          = null;

        foreach ((new ClientDumpsIterator($path))->getResponseIterator() as $object) {
            if ($object instanceof ViewAsset) {
                $resellers[] = $object->resellerId ?? null;
                $resellers[] = $object->reseller->id ?? null;
                $customers[] = $object->customerId ?? null;
                $customers[] = $object->customer->id ?? null;
                $oem         = $object->vendor ?? null;
            } elseif ($object instanceof ViewAssetDocument) {
                $resellers[] = $object->reseller->id ?? null;
                $customers[] = $object->customer->id ?? null;
                $oems[]      = [
                    $object->document->vendorSpecificFields->vendor ?? $oem ?? null,
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

    abstract protected function generateData(string $path): bool;
}
