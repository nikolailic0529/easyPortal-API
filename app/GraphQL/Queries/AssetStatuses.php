<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;

class AssetStatuses {
    public function __invoke(Builder $builder): Builder {
        return $builder->where('object_type', '=', (new Asset())->getMorphClass());
    }
}
