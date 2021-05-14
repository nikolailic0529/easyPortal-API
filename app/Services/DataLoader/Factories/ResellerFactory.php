<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Reseller;
use App\Services\DataLoader\Events\ResellerUpdated;
use App\Services\DataLoader\Factories\Concerns\WithLocations;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Location;
use App\Services\DataLoader\Schema\Type;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

use function array_map;
use function sprintf;

class ResellerFactory extends ModelFactory {
    use WithLocations;

    protected ?LocationFactory $locations = null;

    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected TypeResolver $types,
        protected ResellerResolver $resellers,
        protected Dispatcher $events,
    ) {
        parent::__construct($logger, $normalizer);
    }

    // <editor-fold desc="Settings">
    // =========================================================================
    public function setLocationFactory(?LocationFactory $factory): static {
        $this->locations = $factory;

        return $this;
    }

    protected function shouldUpdateLocations(): bool {
        return (bool) $this->locations;
    }
    // </editor-fold>

    // <editor-fold desc="Prefetch">
    // =========================================================================
    /**
     * @param array<\App\Services\DataLoader\Schema\Company> $resellers
     * @param \Closure(\Illuminate\Database\Eloquent\Collection):void|null $callback
     */
    public function prefetch(array $resellers, bool $reset = false, Closure|null $callback = null): static {
        $keys = array_map(static function (Company $reseller): string {
            return $reseller->id;
        }, $resellers);

        $this->resellers->prefetch($keys, $reset, $callback);

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Factory">
    // =========================================================================
    public function find(Type $type): ?Reseller {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::find($type);
    }

    public function create(Type $type): ?Reseller {
        $model = null;

        if ($type instanceof Company) {
            $model = $this->createFromCompany($type);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                Location::class,
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
        $factory  = $this->factory(function (Reseller $reseller) use (&$created, $company): Reseller {
            $created        = !$reseller->exists;
            $reseller->id   = $this->normalizer->uuid($company->id);
            $reseller->name = $this->normalizer->string($company->name);

            if ($this->locations) {
                $reseller->locations = $this->objectLocations($reseller, $company->locations);
            }

            $reseller->save();

            $this->events->dispatch(new ResellerUpdated($reseller, $company));

            return $reseller;
        });
        $reseller = $this->resellers->get(
            $company->id,
            static function () use ($factory): Reseller {
                return $factory(new Reseller());
            },
        );

        // Update
        if (!$created && !$this->isSearchMode()) {
            $factory($reseller);
        }

        // Return
        return $reseller;
    }
    // </editor-fold>
}
