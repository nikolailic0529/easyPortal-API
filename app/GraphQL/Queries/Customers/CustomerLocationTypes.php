<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Customers;

use App\Models\CustomerLocation;
use App\Models\Data\Type;
use Illuminate\Database\Eloquent\Builder;

class CustomerLocationTypes {
    /**
     * @return Builder<Type>
     */
    public function __invoke(): Builder {
        return Type::query()
            ->where('object_type', '=', (new CustomerLocation())->getMorphClass())
            ->orderByKey();
    }
}
