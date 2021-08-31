<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders\Concerns;

use App\Models\Asset;
use App\Models\Callbacks\GetKey;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Reseller;
use App\Services\DataLoader\Resolver;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Utils\ModelHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

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

                    continue;
                }
            } catch (Throwable $exception) {
                $this->getLogger()->warning(__METHOD__, [
                    'ids'       => $objects->map(new GetKey()),
                    'resolver'  => $resolver::class,
                    'exception' => $exception,
                ]);
            }

            foreach ($resolver->getResolved() as $object) {
                try {
                    if ($object instanceof Reseller) {
                        $this->updateResellerCalculatedProperties($object);
                    } else {
                        throw new InvalidArgumentException(sprintf(
                            'Impossible to update calculated properties for `%s`.',
                            $object::class,
                        ));
                    }
                } catch (Throwable $exception) {
                    $this->getLogger()->warning(__METHOD__, [
                        'object'    => $object,
                        'exception' => $exception,
                    ]);
                }
            }
        }
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Customer> $customers
     */
    protected function updateCustomersCalculatedProperties(Collection $customers): void {
        $customers  = $customers->keyBy(new GetKey());
        $statistics = (new ModelHelper(Customer::query()))
            ->getRelation('assets')
            ->toBase()
            ->select('customer_id', DB::raw('count(*) as assets_count'))
            ->whereIn('customer_id', $customers->map(new GetKey())->all())
            ->groupBy('customer_id')
            ->get();

        foreach ($statistics as $data) {
            $customer = $customers[$data->customer_id];

            if ($customer instanceof Customer) {
                $customer->assets_count = $data->assets_count;
                $customer->save();
            }
        }
    }

    protected function updateResellerCalculatedProperties(Reseller $reseller): void {
        $assetsCustomers   = Asset::query()
            ->toBase()
            ->distinct()
            ->select('customer_id')
            ->where('reseller_id', '=', $reseller->getKey());
        $documentsCustomer = Document::query()
            ->toBase()
            ->distinct()
            ->select('customer_id')
            ->where('reseller_id', '=', $reseller->getKey());
        $ids               = $assetsCustomers
            ->union($documentsCustomer)
            ->get()
            ->pluck('customer_id');
        $customers         = new Collection();

        if (!$ids->isEmpty()) {
            $customers = Customer::query()
                ->whereIn((new Customer())->getKeyName(), $ids)
                ->get();
        }

        $reseller->customers    = $customers;
        $reseller->assets_count = $reseller->assets()->count();
        $reseller->save();
    }
}
