<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Model;
use App\Models\Status;
use App\Models\Type;
use App\Services\DataLoader\Exceptions\DataLoaderException;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Schema\Company as CompanyObject;
use App\Services\DataLoader\Schema\CompanyType;
use Illuminate\Support\Collection;

use function array_map;
use function array_unique;
use function count;
use function reset;

trait Company {
    use WithStatus;
    use WithType;

    abstract protected function getNormalizer(): Normalizer;

    /**
     * @return array<\App\Models\Status>
     */
    protected function companyStatuses(Model $owner, CompanyObject $company): array {
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
            throw new DataLoaderException('Multiple type.');
        } elseif (count($names) < 1) {
            throw new DataLoaderException('Type is missing.');
        } else {
            $type = $this->type($owner, reset($names));
        }

        return $type;
    }
}
