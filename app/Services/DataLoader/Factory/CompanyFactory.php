<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory;

use App\Models\Status;
use App\Models\Type;
use App\Services\DataLoader\Exceptions\FailedToProcessCompanyMultipleTypes;
use App\Services\DataLoader\Exceptions\FailedToProcessCompanyUnknownType;
use App\Services\DataLoader\Factory\Concerns\WithContacts;
use App\Services\DataLoader\Factory\Concerns\WithLocations;
use App\Services\DataLoader\Factory\Concerns\WithStatus;
use App\Services\DataLoader\Factory\Concerns\WithType;
use App\Services\DataLoader\Factory\Factories\ContactFactory;
use App\Services\DataLoader\Factory\Factories\LocationFactory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\CompanyType;
use App\Utils\Eloquent\Model;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Collection;

use function array_map;
use function array_unique;
use function count;
use function reset;

/**
 * @template TCompany of \App\Models\Reseller|\App\Models\Customer
 * @template TLocation of \App\Models\ResellerLocation|\App\Models\CustomerLocation
 *
 * @extends ModelFactory<TCompany>
 */
abstract class CompanyFactory extends ModelFactory {
    use WithType;
    use WithStatus;
    use WithContacts;

    /**
     * @phpstan-use WithLocations<TCompany, TLocation>
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
     * @return array<Status>
     */
    protected function companyStatuses(Model $owner, Company $company): array {
        return (new Collection($company->status ?? []))
            ->filter(function (?string $status): bool {
                return (bool) $this->getNormalizer()->string($status);
            })
            ->map(function (string $status) use ($owner): Status {
                return $this->status($owner, $status);
            })
            ->unique()
            ->all();
    }

    /**
     * @param array<CompanyType> $types
     */
    protected function companyType(Model $owner, array $types): Type {
        $type  = null;
        $names = array_unique(array_map(static function (CompanyType $type): string {
            return $type->type;
        }, $types));

        if (count($names) > 1) {
            throw new FailedToProcessCompanyMultipleTypes($owner->getKey(), $names);
        } elseif (count($names) < 1) {
            throw new FailedToProcessCompanyUnknownType($owner->getKey());
        } else {
            $type = $this->type($owner, reset($names));
        }

        return $type;
    }
    // </editor-fold>
}
