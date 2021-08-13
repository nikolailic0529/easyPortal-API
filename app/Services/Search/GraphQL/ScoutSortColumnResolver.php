<?php declare(strict_types = 1);

namespace App\Services\Search\GraphQL;

use App\Services\Search\Configuration;
use Illuminate\Database\Eloquent\Model;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Contracts\ScoutColumnResolver;

use function implode;

class ScoutSortColumnResolver implements ScoutColumnResolver {
    /**
     * @inheritDoc
     */
    public function getColumn(Model $model, array $path): string {
        return Configuration::getPropertyName(implode('.', $path));
    }
}
