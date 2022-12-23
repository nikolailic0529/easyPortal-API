<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Models\Reseller;
use App\Models\ResellerLocation;
use App\Services\DataLoader\Events\ResellerUpdated;
use App\Services\DataLoader\Factory\CompanyFactory;
use App\Services\DataLoader\Factory\Concerns\WithKpi;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolver\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;

use function implode;
use function sprintf;

/**
 * @extends CompanyFactory<Reseller, ResellerLocation>
 */
class ResellerFactory extends CompanyFactory {
    use WithKpi;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Normalizer $normalizer,
        TypeResolver $typeResolver,
        StatusResolver $statusResolver,
        ContactFactory $contactFactory,
        LocationFactory $locationFactory,
        protected Dispatcher $dispatcher,
        protected ResellerResolver $resellerResolver,
    ) {
        parent::__construct(
            $exceptionHandler,
            $normalizer,
            $typeResolver,
            $statusResolver,
            $contactFactory,
            $locationFactory,
        );
    }

    // <editor-fold desc="Factory">
    // =========================================================================
    public function getModel(): string {
        return Reseller::class;
    }

    public function create(Type $type): ?Reseller {
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

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function createFromCompany(Company $company): ?Reseller {
        // Get/Create
        $created  = false;
        $factory  = function (Reseller $reseller) use (&$created, $company): Reseller {
            $created              = !$reseller->exists;
            $normalizer           = $this->getNormalizer();
            $reseller->id         = $company->id;
            $reseller->name       = $normalizer->string($company->name);
            $reseller->changed_at = $normalizer->datetime($company->updatedAt);
            $reseller->statuses   = $this->companyStatuses($reseller, $company);
            $reseller->contacts   = $this->objectContacts($reseller, $company->companyContactPersons);
            $reseller->locations  = $this->companyLocations($reseller, $company->locations);
            $reseller->kpi        = $this->kpi($reseller, $company->companyKpis);

            if ($created) {
                $reseller->assets_count    = 0;
                $reseller->customers_count = 0;
            }

            if ($reseller->trashed()) {
                $reseller->restore();
            } else {
                $reseller->save();
            }

            $this->dispatcher->dispatch(new ResellerUpdated($reseller, $company));

            return $reseller;
        };
        $reseller = $this->resellerResolver->get(
            $company->id,
            static function () use ($factory): Reseller {
                return $factory(new Reseller());
            },
        );

        // Update
        if (!$created) {
            $factory($reseller);
        }

        // Return
        return $reseller;
    }
    // </editor-fold>
}
