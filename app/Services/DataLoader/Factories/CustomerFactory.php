<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Customer;
use App\Services\DataLoader\FactoryPrefetchable;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

use function implode;
use function sprintf;

// TODO [DataLoader] Customer can be a CUSTOMER or RESELLER or any other type.
//      If this is not true we need to update this factory and its tests.

class CustomerFactory extends CompanyFactory implements FactoryPrefetchable {
    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        Dispatcher $dispatcher,
        TypeResolver $typeResolver,
        StatusResolver $statusResolver,
        ContactFactory $contactFactory,
        LocationFactory $locationFactory,
        protected CustomerResolver $customerResolver,
    ) {
        parent::__construct(
            $logger,
            $normalizer,
            $dispatcher,
            $typeResolver,
            $statusResolver,
            $contactFactory,
            $locationFactory,
        );
    }

    // <editor-fold desc="Factory">
    // =========================================================================
    public function find(Type $type): ?Customer {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::find($type);
    }

    public function create(Type $type): ?Customer {
        $model = null;

        if ($type instanceof Company) {
            $model = $this->createFromCompany($type);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                implode('`, `', [
                    Company::class,
                ]),
            ));
        }

        return $model;
    }
    // </editor-fold>

    // <editor-fold desc="Prefetch">
    // =========================================================================
    /**
     * @param array<\App\Services\DataLoader\Schema\Company|\App\Services\DataLoader\Schema\ViewAsset> $objects
     * @param \Closure(\Illuminate\Database\Eloquent\Collection):void|null $callback
     */
    public function prefetch(array $objects, bool $reset = false, Closure|null $callback = null): static {
        $keys = (new Collection($objects))
            ->map(static function (Company|ViewAsset $model): array {
                $keys = [];

                if ($model instanceof Company) {
                    $keys[] = $model->id;
                } elseif ($model instanceof ViewAsset) {
                    $keys[] = $model->customerId;

                    if (isset($model->assetDocument)) {
                        foreach ($model->assetDocument as $assetDocument) {
                            $keys[] = $assetDocument->customer->id ?? null;
                            $keys[] = $assetDocument->document->customerId ?? null;
                        }
                    }
                } else {
                    // empty
                }

                return $keys;
            })
            ->flatten()
            ->unique()
            ->filter()
            ->all();

        $this->customerResolver->prefetch($keys, $reset, $callback);

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function createFromCompany(Company $company): ?Customer {
        // Get/Create customer
        $created  = false;
        $factory  = $this->factory(function (Customer $customer) use (&$created, $company): Customer {
            $created              = !$customer->exists;
            $normalizer           = $this->getNormalizer();
            $customer->id         = $normalizer->uuid($company->id);
            $customer->name       = $normalizer->string($company->name);
            $customer->type       = $this->companyType($customer, $company->companyTypes);
            $customer->changed_at = $normalizer->datetime($company->updatedAt);
            $customer->statuses   = $this->companyStatuses($customer, $company);
            $customer->contacts   = $this->objectContacts($customer, $company->companyContactPersons);
            $customer->locations  = $this->objectLocations($customer, $company->locations);

            $customer->save();

            return $customer;
        });
        $customer = $this->customerResolver->get($company->id, static function () use ($factory): Customer {
            return $factory(new Customer());
        });

        // Update
        if (!$created && !$this->isSearchMode()) {
            $factory($customer);
        }

        // Return
        return $customer;
    }
    //</editor-fold>
}
