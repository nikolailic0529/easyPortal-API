<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Location;
use App\Models\Type;
use Illuminate\Database\Eloquent\Builder;

class LocationTypes {
    public function __invoke(): Builder {
        return Type::query()->where('object_type', '=', (new Location())->getMorphClass());
    }
}
