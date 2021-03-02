<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Customer;
use App\Models\Type;
use Illuminate\Support\Collection;

class CustomerTypes {
    /**
     * @param array<string, mixed> $args
     */
    public function __invoke(mixed $_, array $args): Collection {
        return Type::query()
            ->where('object_type', '=', (new Customer())->getMorphClass())
            ->get();
    }
}
