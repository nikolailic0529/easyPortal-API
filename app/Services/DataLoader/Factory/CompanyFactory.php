<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory;

use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Data\Status;
use App\Models\Reseller;
use App\Models\ResellerLocation;
use App\Services\DataLoader\Factory\Concerns\WithContacts;
use App\Services\DataLoader\Factory\Concerns\WithLocations;
use App\Services\DataLoader\Factory\Concerns\WithStatus;
use App\Services\DataLoader\Factory\Concerns\WithType;
use App\Services\DataLoader\Factory\Factories\ContactFactory;
use App\Services\DataLoader\Factory\Factories\LocationFactory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Types\Company;
use App\Utils\Eloquent\Model;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Collection;

use function array_values;

/**
 * @template TCompany of Reseller|Customer
 * @template TLocation of ResellerLocation|CustomerLocation
 *
 * @extends ModelFactory<TCompany>
 */
abstract class CompanyFactory extends ModelFactory {
    use WithType;
    use WithStatus;
    use WithContacts;

    /**
     * @use WithLocations<TCompany, TLocation>
     */
    use WithLocations;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Normalizer $normalizer,
        protected TypeResolver $typeResolver,
        protected StatusResolver $statusResolver,
        protected ContactFactory $contactFactory,
        protected LocationFactory $locationFactory,
    ) {
        parent::__construct($exceptionHandler, $normalizer);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getContactsFactory(): ContactFactory {
        return $this->contactFactory;
    }

    protected function getLocationFactory(): LocationFactory {
        return $this->locationFactory;
    }

    protected function getStatusResolver(): StatusResolver {
        return $this->statusResolver;
    }

    protected function getTypeResolver(): TypeResolver {
        return $this->typeResolver;
    }
    // </editor-fold>

    // <editor-fold desc="Company">
    // =========================================================================
    /**
     * @return Collection<int, Status>
     */
    protected function companyStatuses(Model $owner, Company $company): Collection {
        $statuses = [];

        foreach ($company->status ?? [] as $status) {
            if ($status) {
                $status                      = $this->status($owner, $status);
                $statuses[$status->getKey()] = $status;
            }
        }

        return Collection::make(array_values($statuses));
    }
    // </editor-fold>
}
