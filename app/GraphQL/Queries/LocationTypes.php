<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;

class LocationTypes {
    public function __invoke(Builder $builder): Builder {
        return $builder->where('object_type', '=', (new Location())->getMorphClass());
    }
}
