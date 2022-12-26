<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Reseller;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Schema\Types\CompanyKpis;
use App\Services\DataLoader\Schema\Types\Document;
use App\Services\DataLoader\Schema\Types\ViewAsset;
use App\Services\DataLoader\Schema\Types\ViewAssetDocument;
use App\Services\DataLoader\Schema\Types\ViewDocument;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
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
        $context   = [];
        $models    = [
            Context::DISTRIBUTORS => Distributor::class,
            Context::RESELLERS    => Reseller::class,
            Context::CUSTOMERS    => Customer::class,
            Context::ASSETS       => Asset::class,
        ];
        $collected = [
            Context::DISTRIBUTORS => $distributors,
            Context::RESELLERS    => $resellers,
            Context::CUSTOMERS    => $customers,
            Context::ASSETS       => $assets,
            Context::TYPES        => $types,
        ];

        foreach ($collected as $key => $value) {
            $keys = array_values(array_filter(array_unique($value, SORT_REGULAR)));

            if (isset($models[$key])) {
                $model = new $models[$key]();
                $keys  = GlobalScopes::callWithoutAll(static function () use ($model, $keys): array {
                    return $model::query()
                        ->whereIn($model->getKeyName(), $keys)
                        ->withTrashed()
                        ->pluck($model->getKeyName())
                        ->all();
                });
            }

            if ($keys) {
                $context[$key] = $keys;
            }
        }

        // Return
        return $context;
    }
}
