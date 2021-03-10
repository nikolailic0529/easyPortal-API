<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\Organization;
use App\Services\DataLoader\Provider;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class OrganizationProvider extends Provider {
    public function get(string|int $id, Closure $factory = null): ?Organization {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($id, $factory);
    }

    protected function getFindQuery(mixed $key): ?Builder {
        return Organization::whereKey($key);
    }
}
