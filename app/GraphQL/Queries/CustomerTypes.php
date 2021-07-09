<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Customer;
use App\Models\Type;
use Illuminate\Database\Eloquent\Builder;

class CustomerTypes {
    public function __invoke(): Builder {
        return Type::query()
            ->where('object_type', '=', (new Customer())->getMorphClass())
            ->orderByKey();
    }
}
