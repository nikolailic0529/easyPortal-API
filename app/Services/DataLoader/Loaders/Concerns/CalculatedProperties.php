<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders\Concerns;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Reseller;
use App\Services\DataLoader\Resolver;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

use function sprintf;

trait CalculatedProperties {
    abstract protected function getLogger(): LoggerInterface;

    protected function updateCalculatedProperties(Resolver ...$resolvers): void {
        foreach ($resolvers as $resolver) {
            foreach ($resolver->getResolved() as $object) {
                try {
                    if ($object instanceof Reseller) {
                        $this->updateResellerCalculatedProperties($object);
                    } elseif ($object instanceof Customer) {
                        $this->updateCustomerCalculatedProperties($object);
                    } else {
                        throw new InvalidArgumentException(sprintf(
                            'Impossible to update calculated properties for `%s`.',
                            $object::class,
                        ));
                    }
                } catch (Throwable $exception) {
                    $this->getLogger()->warning(__METHOD__, [
                        'reseller'  => $object,
                        'exception' => $exception,
                    ]);
                }
            }
        }
    }

    protected function updateCustomerCalculatedProperties(Customer $customer): void {
        $customer->assets_count = $customer->assets()->count();
        $customer->save();
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
        $customers         = Customer::query()
            ->whereIn((new Customer())->getKeyName(), $ids)
            ->get();

        $reseller->customers    = $customers;
        $reseller->assets_count = $reseller->assets()->count();
        $reseller->save();
    }
}
