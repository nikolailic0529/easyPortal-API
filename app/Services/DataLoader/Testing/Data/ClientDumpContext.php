<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Schema\CompanyKpis;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use Faker\Generator;

use function array_filter;
use function array_unique;
use function array_values;

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

        foreach ((new ClientDumpsIterator($path))->getResponseIterator() as $object) {
            if ($object instanceof ViewAsset) {
                $resellers[] = $object->resellerId ?? null;
                $resellers[] = $object->reseller->id ?? null;
                $customers[] = $object->customerId ?? null;
                $customers[] = $object->customer->id ?? null;
                $assets[]    = $object->id ?? null;
            } elseif ($object instanceof ViewAssetDocument) {
                $resellers[] = $object->reseller->id ?? null;
                $customers[] = $object->customer->id ?? null;
                $types[]     = $object->document->type ?? null;
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
        ];

        foreach ($context as $key => $value) {
            $context[$key] = array_values(array_filter(array_unique($value, SORT_REGULAR)));
        }

        $context = array_filter($context);

        // Return
        return $context;
    }
}
