<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Customer;
use App\Models\Status;
use Illuminate\Database\Eloquent\Builder;

class CustomerStatuses {
    public function __invoke(): Builder {
        return Status::query()
            ->where('object_type', '=', (new Customer())->getMorphClass())
            ->orderByKey();
    }
}
