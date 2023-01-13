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

    protected function getDistributorResolver(): DistributorResolver {
        return $this->distributorResolver;
    }

    public function getModel(): string {
        return Distributor::class;
    }

    public function create(Type $type, bool $force = false): ?Distributor {
        $model = null;

        if ($type instanceof Company) {
            $model = $this->createFromCompany($type, $force);
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
    protected function createFromCompany(Company $company, bool $force): ?Distributor {
        return $this->getDistributorResolver()->get(
            $company->id,
            static function (?Distributor $distributor) use ($force, $company): Distributor {
                // Unchanged?
                $hash = $company->getHash();

                if ($force === false && $distributor !== null && $hash === $distributor->hash) {
                    return $distributor;
                }

                // Update
                $distributor           ??= new Distributor();
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
            },
        );
    }
    //</editor-fold>
}
