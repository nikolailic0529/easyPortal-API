<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Distributor;
use App\Services\DataLoader\FactoryPrefetchable;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\DistributorResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;

use function array_map;
use function implode;
use function sprintf;

class DistributorFactory extends ModelFactory implements FactoryPrefetchable {
    public function __construct(
        ExceptionHandler $exceptionHandler,
        Normalizer $normalizer,
        protected TypeResolver $typeResolver,
        protected DistributorResolver $distributorResolver,
    ) {
        parent::__construct($exceptionHandler, $normalizer);
    }

    // <editor-fold desc="Prefetch">
    // =========================================================================
    /**
     * @param array<\App\Services\DataLoader\Schema\Company> $distributors
     * @param \Closure(\Illuminate\Database\Eloquent\Collection):void|null $callback
     */
    public function prefetch(array $distributors, bool $reset = false, Closure|null $callback = null): static {
        $keys = array_map(static function (Company $distributor): string {
            return $distributor->id;
        }, $distributors);

        $this->distributorResolver->prefetch($keys, $callback);

        return $this;
    }

    // </editor-fold>

    public function find(Type $type): ?Distributor {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
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
            $distributor->synced_at  = Date::now();

            $distributor->save();

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
