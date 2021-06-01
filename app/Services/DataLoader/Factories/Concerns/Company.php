<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Model;
use App\Models\Status;
use App\Models\Type;
use App\Services\DataLoader\Exceptions\DataLoaderException;
use App\Services\DataLoader\Schema\CompanyType;

use function array_map;
use function array_unique;
use function count;
use function reset;

trait Company {
    use WithStatus;
    use WithType;

    /**
     * @param array<\App\Services\DataLoader\Schema\CompanyType> $statuses
     */
    protected function companyStatus(Model $owner, array $statuses): Status {
        $status = null;
        $names  = array_unique(array_map(static function (CompanyType $type): string {
            return $type->status;
        }, $statuses));

        if (count($names) > 1) {
            throw new DataLoaderException('Multiple status.');
        } elseif (count($names) < 1) {
            throw new DataLoaderException('Status is missing.');
        } else {
            $status = $this->status($owner, reset($names));
        }

        return $status;
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
