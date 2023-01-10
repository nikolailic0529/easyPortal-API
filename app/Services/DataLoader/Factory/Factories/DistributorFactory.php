<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Models\Distributor;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Resolver\Resolvers\DistributorResolver;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\Types\Company;
use Illuminate\Contracts\Debug\ExceptionHandler;
use InvalidArgumentException;

use function implode;
use function sprintf;

/**
 * @extends Factory<Distributor>
 */
class DistributorFactory extends Factory {
    public function __construct(
        ExceptionHandler $exceptionHandler,
        protected TypeResolver $typeResolver,
        protected DistributorResolver $distributorResolver,
    ) {
        parent::__construct($exceptionHandler);
    }

    public function getModel(): string {
        return Distributor::class;
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
        $factory     = static function (Distributor $distributor) use (&$created, $company): Distributor {
            // Unchanged?
            $created = !$distributor->exists;
            $hash    = $company->getHash();

            if ($hash === $distributor->hash) {
                return $distributor;
            }

            // Update
            $distributor->id         = $company->id;
            $distributor->hash       = $hash;
            $distributor->name       = $company->name;
            $distributor->changed_at = $company->updatedAt;

            if ($distributor->trashed()) {
                $distributor->restore();
            } else {
                $distributor->save();
            }

            return $distributor;
        };
        $distributor = $this->distributorResolver->get(
            $company->id,
            static function () use ($factory): Distributor {
                return $factory(new Distributor());
            },
        );

        // Update
        if (!$created) {
            $factory($distributor);
        }

        // Return
        return $distributor;
    }
    //</editor-fold>
}
