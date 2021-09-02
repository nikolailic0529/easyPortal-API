<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders\Concerns;

use App\Models\Asset;
use App\Models\Callbacks\GetKey;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Reseller;
use App\Services\DataLoader\Resolver;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Utils\ModelHelper;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use stdClass;
use Throwable;

use function array_fill_keys;
use function sprintf;

trait CalculatedProperties {
    abstract protected function getLogger(): LoggerInterface;

    protected function updateCalculatedProperties(Resolver ...$resolvers): void {
        foreach ($resolvers as $resolver) {
            // Empty?
            $objects = $resolver->getResolved();

            if ($objects->isEmpty()) {
                continue;
            }

            // Update
            try {
                if ($resolver instanceof CustomerResolver) {
                    $this->updateCustomersCalculatedProperties($objects);
                } elseif ($resolver instanceof ResellerResolver) {
                    $this->updateResellersCalculatedProperties($objects);
                } else {
                    throw new InvalidArgumentException(sprintf(
                        'Impossible to update calculated properties for `%s`.',
                        $resolver::class,
                    ));
                }
            } catch (Throwable $exception) {
                $this->getLogger()->warning(__METHOD__, [
                    'ids'       => $objects->map(new GetKey()),
                    'resolver'  => $resolver::class,
                    'exception' => $exception,
                ]);
            }
        }
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Customer> $customers
     */
    protected function updateCustomersCalculatedProperties(Collection $customers): void {
        $customers = $customers->keyBy(new GetKey());
        $assets    = (new ModelHelper(Customer::query()))
            ->getRelation('assets')
            ->toBase()
            ->select('customer_id', DB::raw('count(*) as assets_count'))
            ->whereIn('customer_id', $customers->map(new GetKey())->all())
            ->groupBy('customer_id')
            ->get()
            ->keyBy(static function (stdClass $row): string {
                return $row->customer_id;
            });

        foreach ($customers as $customer) {
            /** @var \App\Models\Customer $customer */
            $customer->assets_count = $assets[$customer->getKey()]->assets_count ?? $customer->assets_count ?? 0;

            $customer->save();
        }
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Reseller> $resellers
     */
    protected function updateResellersCalculatedProperties(Collection $resellers): void {
        $resellers = $resellers->keyBy(new GetKey());
        $customers = $this->getResellersCustomers($resellers, [Asset::class, Document::class]);
        $assets    = (new ModelHelper(Reseller::query()))
            ->getRelation('assets')
            ->toBase()
            ->select('reseller_id', DB::raw('count(*) as assets_count'))
            ->whereIn('reseller_id', $resellers->map(new GetKey())->all())
            ->groupBy('reseller_id')
            ->get()
            ->keyBy(static function (stdClass $row): string {
                return $row->reseller_id;
            });

        foreach ($resellers as $reseller) {
            /** @var \App\Models\Reseller $reseller */
            $reseller->assets_count = $assets[$reseller->getKey()]->assets_count ?? $reseller->assets_count ?? 0;
            $reseller->customers    = $customers[$reseller->getKey()] ?? $reseller->customers ?? [];

            $reseller->save();

            /** Loaded inside {@see getResellersCustomers()} and not needed anymore. */
            unset($reseller->customers);
        }
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Reseller> $resellers
     * @param array<class-string<\App\Models\Model>>               $models
     *
     * @return array<string,array<\App\Models\Customer>>
     */
    private function getResellersCustomers(Collection $resellers, array $models): array {
        // Query
        /** @var \Illuminate\Database\Query\Builder $union */
        $union = null;
        $ids   = $resellers->map(new GetKey())->all();

        foreach ($models as $model) {
            $builder = $model::query()
                ->toBase()
                ->distinct()
                ->select('reseller_id', 'customer_id')
                ->whereIn('reseller_id', $ids);

            if ($union) {
                $union->union($builder);
            } else {
                $union = $builder;
            }
        }

        if (!($union instanceof Builder)) {
            return [];
        }

        // Laravel creates a new model instance in each query, we are trying to
        // reuse the models to reduce memory usage.
        $customers = new Collection();
        $resellers = (new EloquentCollection($resellers))->loadMissing('customers');

        foreach ($resellers as $reseller) {
            /** @var \App\Models\Reseller $reseller */
            foreach ($reseller->customers as $customer) {
                /** @var \App\Models\Customer $customer */
                $customers[$customer->getKey()] = $customer;
            }
        }

        // Process
        $key    = (new Customer())->getKeyName();
        $rows   = $union->get();
        $missed = $rows->pluck('customer_id')->diff($customers->keys())->sort()->all();
        $result = array_fill_keys($rows->pluck('reseller_id')->all(), []);

        if ($missed) {
            $customers = $customers->merge(Customer::query()->whereIn($key, $missed)->get()->keyBy(new GetKey()));
        }

        foreach ($rows as $row) {
            $result[$row->reseller_id][] = $customers[$row->customer_id];
        }

        // Return
        return $result;
    }
}
