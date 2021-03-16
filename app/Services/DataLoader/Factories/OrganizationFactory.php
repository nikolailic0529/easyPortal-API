<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Organization;
use App\Services\DataLoader\Factories\Concerns\WithLocations;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\OrganizationResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Location;
use App\Services\DataLoader\Schema\Type;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

use function array_map;
use function sprintf;

class OrganizationFactory extends ModelFactory {
    use WithLocations;

    protected ?LocationFactory $locations = null;

    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected TypeResolver $types,
        protected OrganizationResolver $organizations,
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
     * @param array<\App\Services\DataLoader\Schema\Company> $organizations
     */
    public function prefetch(array $organizations, bool $reset = false): static {
        $keys = array_map(static function (Company $organization): string {
            return $organization->id;
        }, $organizations);

        $this->organizations->prefetch($keys, $reset);

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Factory">
    // =========================================================================
    public function find(Type $type): ?Organization {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::find($type);
    }

    public function create(Type $type): ?Organization {
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
    protected function createFromCompany(Company $company): ?Organization {
        // Get/Create
        $created      = false;
        $factory      = $this->factory(function (Organization $organization) use (&$created, $company): Organization {
            $created            = !$organization->exists;
            $organization->id   = $this->normalizer->uuid($company->id);
            $organization->name = $this->normalizer->string($company->name);

            if ($this->locations) {
                $organization->locations = $this->objectLocations($organization, $company->locations);
            }

            $organization->save();

            return $organization;
        });
        $organization = $this->organizations->get(
            $company->id,
            static function () use ($factory): Organization {
                return $factory(new Organization());
            },
        );

        // Update
        if (!$created && !$this->isSearchMode()) {
            $factory($organization);
        }

        // Return
        return $organization;
    }
    // </editor-fold>
}
