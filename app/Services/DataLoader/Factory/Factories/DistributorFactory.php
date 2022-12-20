<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Models\Distributor;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\DistributorResolver;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;

use function implode;
use function sprintf;

/**
 * @extends ModelFactory<Distributor>
 */
class DistributorFactory extends ModelFactory {
    public function __construct(
        ExceptionHandler $exceptionHandler,
        Normalizer $normalizer,
        protected TypeResolver $typeResolver,
        protected DistributorResolver $distributorResolver,
    ) {
        parent::__construct($exceptionHandler, $normalizer);
    }

    public function getModel(): string {
        return Distributor::class;
    }

    public function find(Type $type): ?Distributor {
        return parent::find($type);
    }

    public function create(Type $type): ?Distributor {
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

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function createFromCompany(Company $company): ?Distributor {
        // Get/Create
        $created     = false;
        $factory     = $this->factory(function (Distributor $distributor) use (&$created, $company): Distributor {
            $created                 = !$distributor->exists;
            $normalizer              = $this->getNormalizer();
            $distributor->id         = $normalizer->uuid($company->id);
            $distributor->name       = $normalizer->string($company->name);
            $distributor->changed_at = $normalizer->datetime($company->updatedAt);

            if ($created) {
                $distributor->synced_at = Date::now();
            }

            if ($distributor->trashed()) {
                $distributor->restore();
            } else {
                $distributor->save();
            }

            return $distributor;
        });
        $distributor = $this->distributorResolver->get(
            $company->id,
            static function () use ($factory): Distributor {
                return $factory(new Distributor());
            },
        );

        // Update
        if (!$created && !$this->isSearchMode()) {
            $factory($distributor);
        }

        // Return
        return $distributor;
    }
    //</editor-fold>
}
