<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Model;
use App\Models\Status;
use App\Models\Type;
use App\Services\DataLoader\Exceptions\FailedToProcessCompanyMultipleTypes;
use App\Services\DataLoader\Exceptions\FailedToProcessCompanyUnknownType;
use App\Services\DataLoader\Factories\Concerns\WithContacts;
use App\Services\DataLoader\Factories\Concerns\WithLocations;
use App\Services\DataLoader\Factories\Concerns\WithStatus;
use App\Services\DataLoader\Factories\Concerns\WithType;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\CompanyType;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Collection;

use function array_map;
use function array_unique;
use function count;
use function reset;

abstract class CompanyFactory extends ModelFactory {
    use WithType;
    use WithStatus;
    use WithContacts;
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
     * @return array<\App\Models\Status>
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
     * @param array<\App\Services\DataLoader\Schema\CompanyType> $types
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
